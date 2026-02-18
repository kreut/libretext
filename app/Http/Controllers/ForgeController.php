<?php

namespace App\Http\Controllers;

use App\AssignmentSyncQuestion;
use App\Enrollment;
use App\Forge;
use App\ForgeAssignmentQuestion;
use App\ForgeEnrollment;
use App\Assignment;
use App\Course;
use App\Exceptions\Handler;
use App\ForgeUserToken;
use App\Question;
use App\SubmissionFile;
use App\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\DateFormatter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ForgeController extends Controller
{

    use DateFormatter;

    /**
     * @var Model|Builder|object|null
     */
    private $secret;

    public function __construct()
    {

        $this->secret = DB::table('key_secrets')
            ->where('key', 'forge')
            ->first()
            ->secret;
    }

    /**
     * @param Request $request
     * @param string $forge_draft_id
     * @param string $grader_central_identity_id
     * @param string $student_central_identity_id
     * @return array
     * @throws Exception
     */
    public function graderHasAccess(Request $request,
                                    string  $forge_draft_id,
                                    string  $grader_central_identity_id,
                                    string  $student_central_identity_id): array
    {

        try {
            $response['type'] = 'error';
            if ($request->bearerToken() !== $this->secret) {
                throw new Exception("Invalid Bearer token");
            }

            $forge_assignment_question = DB::table('assignment_question_forge_draft')
                ->join('assignment_question', 'assignment_question_forge_draft.assignment_question_id', '=', 'assignment_question.id')
                ->where('forge_draft_id', $forge_draft_id)
                ->first();
            if (!$forge_assignment_question) {
                $response['message'] = "There is no Forge assignment question with Forge draft ID $forge_draft_id.";
                return $response;
            }
            $student = User::where('central_identity_id', $student_central_identity_id)->first();
            if (!$student) {
                $response['message'] = "There is no student with UUID $student_central_identity_id.";
                return $response;
            }
            $grader = User::where('central_identity_id', $grader_central_identity_id)->first();
            if (!$grader) {
                $response['message'] = "There is no grader with UUID $grader_central_identity_id.";
                return $response;
            }

            $assignment = Assignment::find($forge_assignment_question->assignment_id);
            $course = $assignment->course;
            $enrollment = Enrollment::where('user_id', $student->id)
                ->where('course_id', $course->id)
                ->first();

            if (!DB::table('graders')->where('user_id', $grader->id)
                ->where('section_id', $enrollment->section_id)
                ->first()) {
                $response['message'] = "The grader with UUID $grader_central_identity_id is not a grader for the student's section.";
                return $response;
            }
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to validate whether the grader with UUID $grader_central_identity_id has access to the forge draft with draft ID $forge_draft_id submitted by the student with UUID $student_central_identity_id: {$e->getMessage()}.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param string $token
     * @return array
     * @throws Exception
     */
    public function getUserFromToken(Request $request, string $token): array
    {
        try {
            $response['type'] = 'error';
            if ($request->bearerToken() !== $this->secret) {
                throw new Exception("Invalid Bearer token");
            }
            $forge_user_token = DB::table('forge_user_tokens')->where('token', $token)->first();
            if (!$forge_user_token) {
                $response['message'] = "No user exists with the token $token.";
                return $response;
            }
            $user = User::find($forge_user_token->user_id);
            $response['uuid'] = $user->fake_student ? $user->id : $user->central_identity_id;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the user from the token: {$e->getMessage()}.";
        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param User $student
     * @return array
     * @throws Exception
     */
    public function allowResubmission(Assignment $assignment,
                                      Question   $question,
                                      User       $student): array
    {

        try {
            $response['type'] = 'error';
            $submissionFile = SubmissionFile::where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $student->id)
                ->first();
            $authorized = Gate::inspect('allowResubmission',
                $submissionFile);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $submissionFile->upload_count = 0;
            $submissionFile->save();
            $response['type'] = 'success';
            $response['message'] = 'The student may resubmit up until the due date of the question.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to allow the resubmission.  Please try again or contact us.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param User $student
     * @param ForgeAssignmentQuestion $forgeAssignmentQuestion
     * @param ForgeEnrollment $forgeEnrollment
     * @param ForgeUserToken $forgeUserToken
     * @return array
     * @throws Exception
     */
    public function getSubmissionByAssignmentQuestionStudent(Request                 $request,
                                                             Assignment              $assignment,
                                                             Question                $question,
                                                             User                    $student,
                                                             ForgeAssignmentQuestion $forgeAssignmentQuestion,
                                                             ForgeEnrollment         $forgeEnrollment,
                                                             ForgeUserToken          $forgeUserToken): array
    {
        try {
            $response['type'] = 'error';

            $authorized = Gate::inspect('getSubmissionByAssignmentQuestionStudent',
                [$forgeAssignmentQuestion, $assignment, $student->central_identity_id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }


            if (!DB::table('submission_files')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $student->id)
                ->first()) {
                $response['submission_id'] = null;
                $response['type'] = 'success';
                return $response;
            }

            $parent_question_id = $question->forge_source_id ?: $question->id;
            $forge_assignment_question = $forgeAssignmentQuestion->where('adapt_assignment_id', $assignment->id)
                ->where('adapt_question_id', $parent_question_id)
                ->first();

            if (!$forge_assignment_question) {
                $response['message'] = "There is no Forge assignment question associated with assignment-parent question ID $assignment->id-$parent_question_id.";
                return $response;
            }

            $assignment_question_forge_draft = DB::table('assignment_question')
                ->join('assignment_question_forge_draft', 'assignment_question.id', 'assignment_question_forge_draft.assignment_question_id')
                ->where('assignment_question.assignment_id', $assignment->id)
                ->where('assignment_question.question_id', $question->id)
                ->first();

            if (!$assignment_question_forge_draft) {
                $response['message'] = "There is no Forge assignment question draft associated with assignment-parent question ID $assignment->id-$question->id.";
                return $response;
            }

            if ($request->user()->role === 4) {
                if (!$forgeEnrollment->where('user_id', $request->user()->id)
                    ->where('course_id', $assignment->course_id)
                    ->first()) {
                    $data = [
                        'assistantId' => $request->user()->central_identity_id,
                        'courseId' => strval($assignment->course->id),
                    ];
                    $http_response = $forgeEnrollment->store($data, 4);
                    if ($http_response->successful()) {
                        $json_response = $http_response->json();
                        if ($json_response['type'] === 'success') {
                            $forgeEnrollment = new ForgeEnrollment();
                            $forgeEnrollment->user_id = $request->user()->id;
                            $forgeEnrollment->course_id = $assignment->course_id;
                            $forgeEnrollment->save();
                        } else {
                            $response['message'] = "Could not enroll TA in Forge course: " . $json_response['message'];
                            return $response;
                        }
                    } else {
                        $response['message'] = "Could not enroll TA in Forge course: " . $http_response->json()['message'];
                        return $response;
                    }
                }
            }

            $http_response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $this->secret",
            ])->post(config('services.antecedent.url') . '/api/adapt/submission', [
                'studentId' => $student->central_identity_id,
                'forgeQuestionId' => $forge_assignment_question->forge_question_id,
            ]);

            if ($http_response->successful()) {
                $json_response = $http_response->json();
                if ($json_response['type'] === 'success') {
                    $response['forge_draft_id'] = $assignment_question_forge_draft->forge_draft_id;
                    $response['submission_id'] = $json_response['data']['submissionId'];
                    $response['domain'] = config('services.antecedent.url');
                    $response['type'] = 'success';
                    $response['token'] = $forgeUserToken->create($request->user());
                }
            } else {
                if (in_array($http_response->json()['message'], ["Failed to get submission ID.", "Submission not found for this assignment associated with the student."])) {
                    $response['submission_id'] = null;
                    $response['type'] = 'success';
                } else {
                    $response['message'] = "Forge Error for studentId $student->central_identity_id, forgeQuestionId $forge_assignment_question->forge_question_id: " . $http_response->json()['message'];
                }
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the submission from Forge for student $student->central_identity_id for ADAPT assignment question ID $assignment->id-$question->id.  Error: {$e->getMessage()}.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param string $forge_draft_id
     * @param string $central_identity_id
     * @param Forge $forge
     * @return array
     * @throws Exception
     */
    public function storeSubmission(
        Request $request,
        string  $forge_draft_id,
        string  $central_identity_id,
        Forge   $forge
    ): array
    {
        $response = ['type' => 'error'];

        try {
            if ($request->bearerToken() !== $this->secret) {
                throw new Exception("Invalid Bearer token");
            }

            $validation = $forge->validateForgeQuestionAccess($forge_draft_id, $central_identity_id);
            if ($validation['type'] === 'error') {
                return $validation;
            }
            $assignment_question_forge_draft = DB::table('assignment_question_forge_draft')
                ->where('forge_draft_id', $forge_draft_id)
                ->first();
            if (!$assignment_question_forge_draft) {
                throw new Exception("No assignment_question_forge_draft exists with forge draft ID $forge_draft_id. Cannot save Forge submission.");
            }

            $assignment_question = AssignmentSyncQuestion::find($assignment_question_forge_draft->assignment_question_id);
            if (!$assignment_question) {
                throw new Exception("No assignment_question exists with ID $assignment_question_forge_draft->assignment_question_id. Cannot save Forge submission.");
            }
            $user = User::where('central_identity_id', $central_identity_id)->first();
            if (!$user) {
                $user = User::where('id', $central_identity_id)->where('fake_student', 1)->first();
                if (!$user) {
                    throw new Exception("No user exists with UUID $central_identity_id.  Cannot save Forge submission.");
                }
            }
            if (SubmissionFile::where('assignment_id', $assignment_question->assignment_id)
                ->where('question_id', $assignment_question->question_id)
                ->where('user_id', $user->id)
                ->where('upload_count', '>=', 1)
                ->first()) {
                throw new Exception("Submission already exists.  Cannot save Forge submission.");
            }
            SubmissionFile::updateOrCreate(
                [
                    'assignment_id' => $assignment_question->assignment_id,
                    'question_id' => $assignment_question->question_id,
                    'user_id' => $user->id
                ],
                [
                    'type' => 'forge',
                    'original_filename' => '',
                    'submission' => '',
                    'date_submitted' => now(),
                    'upload_count' => 1
                ]
            );

            $response['type'] = 'success';
            $response['message'] = "Submission saved to ADAPT.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
            return $response;
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param string $forge_question_id
     * @param string $central_identity_id
     * @param Forge $forge
     * @return array
     * @throws Exception
     */
    public function getAssignTosByAssignmentQuestionUser(Request $request,
                                                         string  $forge_question_id,
                                                         string  $central_identity_id,
                                                         Forge   $forge): array
    {
        try {
            $response['type'] = 'error';

            if ($request->bearerToken() !== $this->secret) {
                throw new Exception("Invalid Bearer token");
            }

            $user = User::where('central_identity_id', $central_identity_id)->first();
            if (!$user) {
                $user = User::where('id', $central_identity_id)->where('fake_student', 1)->first();
                if (!$user) {
                    $response['message'] = "No user exists with UUID $central_identity_id.";
                    return $response;
                }
            }
            $forge_assignment_question = ForgeAssignmentQuestion::where('forge_question_id', $forge_question_id)->first();
            if (!$forge_assignment_question) {
                $response['message'] = "There is no ADAPT assignment question with Forge question ID $forge_question_id.";
                return $response;
            }


            $assignment = Assignment::find($forge_assignment_question->adapt_assignment_id);

            $question = Question::find($forge_assignment_question->adapt_question_id);
            if (!$question) {
                $response['message'] = "There is no ADAPT question with ID $assignment->id $forge_assignment_question->adapt_question_id.";
                return $response;
            }
            $parent_question_id = $question->forge_source_id ?: $question->id;
            $parent_assignment_question = AssignmentSyncQuestion::where('assignment_id', $assignment->id)
                ->where('question_id', $parent_question_id)
                ->first();
            if (!$parent_assignment_question->forge_settings) {
                $response['message'] = "This ADAPT assignment doesn't have its Forge Settings configured.";
                return $response;
            }

            $assignment_question = AssignmentSyncQuestion::where('assignment_id', $assignment->id)
                ->where('question_id', $parent_question_id)
                ->first();

            if (!$assignment_question) {
                $response['message'] = "There is no ADAPT assignment question with ADAPT assignment ID $assignment->id and ADAPT
                question ID $forge_assignment_question->adapt_question_id.";
                return $response;
            }
            $assign_tos = $forge->getAssignToTimingsByAssignmentAndQuestion($assignment_question, $user->id);
            if (!$assign_tos) {
                $response['message'] = "We could not find the draft assign tos for ADAPT user with UUI $central_identity_id";
                return $response;

            }

            $response['assign_tos'] = $forge->getAssignToTimingsByAssignmentAndQuestion($assignment_question, $user->id);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assign tos for that question: {$e->getMessage()}.";
        }

        return $response;

    }

    /**
     * @param Request $request
     * @param string $forge_draft_id
     * @param string $central_identity_id
     * @param Forge $forge
     * @return array
     * @throws Exception
     */
    public function getAssignTosByForgeDraftIdUser(
        Request $request,
        string  $forge_draft_id,
        string  $central_identity_id,
        Forge   $forge
    ): array
    {
        try {

            $response = ['type' => 'error'];
            if ($request->bearerToken() !== $this->secret) {
                throw new Exception("Invalid Bearer token");
            }

            $validation = $forge->validateForgeQuestionAccess($forge_draft_id, $central_identity_id);
            if ($validation['type'] === 'error') {
                return $validation;
            }

            $result = $forge->getAssignToDataForForge($validation, $forge_draft_id, $central_identity_id, $forge);
            if ($result['type'] === 'error') {
                return $result;
            }

            $response['can_submit'] = $result['can_submit'];
            $response['assign_tos'] = $result['assign_tos'];
            $response['adapt_assignment_question_id'] = $result['assignment_question_id'];
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assign tos for that question: {$e->getMessage()}.";
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $parent_question
     * @param Question $current_question
     * @param Forge $forge
     * @return string[]
     * @throws Exception
     */
    public function getAssignTosByAssignmentQuestionLoggedInUser(
        Request    $request,
        Assignment $assignment,
        Question   $parent_question,
        Question   $current_question,
        Forge      $forge
    ): array
    {
        $user = $request->user();
        $response = ['type' => 'error'];

        try {
            $authorized = Gate::inspect('getAssignTosByAssignmentQuestionLoggedInUser',
                [$forge, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $parent_assignment_question = AssignmentSyncQuestion::where('assignment_question.assignment_id', $assignment->id)
                ->where('assignment_question.question_id', $parent_question->id)
                ->first();
            if (!$parent_assignment_question) {
                $response['message'] = "We could not find an assignment question.";
                return $response;
            }


            $draft_assignment_question = AssignmentSyncQuestion::join('assignment_question_forge_draft', 'assignment_question.id', '=', 'assignment_question_forge_draft.assignment_question_id')
                ->where('assignment_question.assignment_id', $assignment->id)
                ->where('assignment_question.question_id', $current_question->id)
                ->first();
            if (!$draft_assignment_question) {
                $response['message'] = "We could not find an assignment question associated with a Forge draft.";
                return $response;
            }

            $assign_tos = $forge->getAssignToTimingsByAssignmentAndQuestionAndDraftId($parent_assignment_question, $user->id, $draft_assignment_question->forge_draft_id);
            if (!$assign_tos) {
                $response['message'] = "We could not find the draft assign tos for draft with UUID $draft_assignment_question->forge_draft_id and ADAPT user with UUID $user->central_identity_id.";
                return $response;
            }
            foreach ($assign_tos as $key => $assign_to) {
                $assign_tos[$key] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assign_to, Auth::user()->time_zone);
            }

            $response['assign_tos'] = $assign_tos;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assign tos for that question: {$e->getMessage()}.";
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Forge $forge
     * @param ForgeAssignmentQuestion $forgeAssignmentQuestion
     * @param ForgeEnrollment $forgeEnrollment
     * @param ForgeUserToken $forgeUserToken
     * @return array
     * @throws Exception
     */
    public function initialize(Request                 $request,
                               Assignment              $assignment,
                               Question                $question,
                               Forge                   $forge,
                               ForgeAssignmentQuestion $forgeAssignmentQuestion,
                               ForgeEnrollment         $forgeEnrollment,
                               ForgeUserToken          $forgeUserToken): array
    {
        try {
            $response['type'] = 'error';

            $adapt_question_id = $question->forge_source_id ? $question->forge_source_id : $question->id;
            $authorized = Gate::inspect('initialize',
                [$forge, $assignment->course, $request->user_id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $forge_assignment_question = $forgeAssignmentQuestion
                ->where('adapt_assignment_id', $assignment->id)
                ->where('adapt_question_id', $adapt_question_id)
                ->first();
            if (!$forge_assignment_question) {
                $instructor = User::find($assignment->course->user_id);
                if ($instructor->id !== $request->user()->id) {
                    $response['message'] = 'Your instructor has not enabled this Forge question.';
                    return $response;
                }
                $central_identity_id = $instructor->central_identity_id;
                if (!$central_identity_id) {
                    throw new Exception("Trying to create forge question with no central identity ID.");
                }
                $data = [
                    'instructorId' => $central_identity_id,
                    'courseId' => strval($assignment->course->id),
                    'questionTitle' => "$question->title-$assignment->name",
                    'questionDescription' => $question->description
                ];
                $forge_response['type'] = 'error';
                if (!app()->environment('local')) {
                    $http_response = $forge->store($data);
                    if ($http_response->successful()) {
                        $forge_response = $http_response->json();
                    } else {
                        $response['message'] = "Forge error initializing question: " . $http_response->json()['message'];
                        return $response;
                    }
                }

                if (app()->environment('local')) {
                    $forge_response['questionId'] = '69651fcc319cfd49976c380c';
                    $forge_response['classId'] = '6964fe39319cfd49976c37ed';
                    $forge_response['type'] = 'success';
                }
                if ($forge_response['type'] === 'success') {
                    $forge_assignment_question = new ForgeAssignmentQuestion();
                    $forge_assignment_question->adapt_assignment_id = $assignment->id;
                    $forge_assignment_question->adapt_question_id = $adapt_question_id;
                    $forge_assignment_question->forge_question_id = $forge_response['questionId'];
                    $forge_assignment_question->forge_class_id = $forge_response['classId'];
                    $forge_assignment_question->save();
                } else {
                    throw new Exception("Could not import forge question: " . $forge_response['message']);
                }
            }
            if ($request->user()->role === 3) {
                if (!$forgeEnrollment->where('user_id', $request->user()->id)
                    ->where('course_id', $assignment->course->id)
                    ->first()) {
                    $fake_student = $request->user()->fake_student;
                    $student_id = $fake_student ? strval($request->user()->id) : strval($request->user()->central_identity_id);
                    $data = ['studentId' => $student_id,
                        'fakeStudent' => (boolean)$fake_student,
                        'forgeQuestionId' => strval($forge_assignment_question->forge_question_id)];
                    $forge_response = $forgeEnrollment->store($data, 3)->json();
                    if ($forge_response['type'] === 'success') {
                        $forgeEnrollment = new ForgeEnrollment();
                        $forgeEnrollment->user_id = $request->user()->id;
                        $forgeEnrollment->course_id = $assignment->course->id;
                        $forgeEnrollment->save();

                    } else {
                        throw new Exception ("Could not enroll student in Forge course: " . $forge_response->message);
                    }
                }
            }

            $assignment_question = AssignmentSyncQuestion::where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            if (!$assignment_question) {
                $response['message'] = "There is no assignment question with assignment_id $assignment->id and question_id $question->id.";
                return $response;
            }

            $assignment_question_forge_draft = DB::table('assignment_question_forge_draft')
                ->where('assignment_question_id', $assignment_question->id)
                ->first();

            $response['token'] = $forgeUserToken->create($request->user());
            $response['type'] = 'success';
            $response['domain'] = config('services.antecedent.url');
            $response['forge_class_id'] = $forge_assignment_question->forge_class_id;
            $response['forge_draft_id'] = $assignment_question_forge_draft->forge_draft_id;
            $response['forge_question_id'] = $forge_assignment_question->forge_question_id;

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }

        return $response;


    }


    /**
     * @param Request $request
     * @param string $central_identity_id
     * @param User $user
     * @return array
     */
    public
    function user(Request $request, string $central_identity_id, User $user): array
    {

        /**Request = { instructorId:uuid, } Response= { firstName:string, lastName:string, email:string }*/
        try {
            $response['type'] = 'error';
            if ($request->bearerToken() !== $this->secret) {
                throw new Exception("Invalid Bearer token");
            }
            $user = $user->where('central_identity_id', $central_identity_id)->first();
            if (!$central_identity_id) {
                $response['message'] = "Central Identity ID is missing in the request.";
                return $response;
            }
            if (!$user) {
                $user = User::where('id', $central_identity_id)->where('fake_student', 1)->first();
                if (!$user) {
                    $response['message'] = "No user exists with UUID $central_identity_id.";
                    return $response;
                }
            }
            switch ($user->role) {
                case(2):
                    $user_type = 'instructor';
                    break;
                case(3):
                    $user_type = 'student';
                    break;
                case(4):
                    $user_type = 'TA';
                    break;
                default:
                    $user_type = "No role defined.";
            }
            $response['firstName'] = $user->first_name;
            $response['lastName'] = $user->last_name;
            $response['email'] = $user->email;
            $response['userType'] = $user_type;
            $response['fakeStudent'] = $user->fake_student;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     */
    public
    function course(Request $request, Course $course): array
    {
        /*Request = { courseId:uuid, } Response= { title:string, startDate:Date, endDate:Date classTimezone:string* }*/
        try {
            $response['type'] = 'error';
            if ($request->bearerToken() !== $this->secret) {
                throw new Exception("Invalid Bearer token");
            }
            $instructor = User::find($course->user_id);
            $response['title'] = $course->name;
            $response['startDate'] = $course->start_date;
            $response['endDate'] = $course->end_date;
            $response['classTimezone'] = $instructor->time_zone;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return $response;

    }


}
