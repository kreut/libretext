<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentFile;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Http\Requests\GradingRequest;
use App\Question;
use App\Score;
use App\Submission;
use App\SubmissionFile;
use App\User;
use Carbon\Carbon;
use Exception;
use App\Traits\DateFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GradingController extends Controller
{

    use DateFormatter;

    /**
     * @param GradingRequest $request
     * @param Assignment $Assignment
     * @param AssignmentFile $assignmentFile
     * @param User $user
     * @param Score $score
     * @return array
     * @throws Exception
     */
    public function store(GradingRequest $request,
                          Assignment     $Assignment,
                          AssignmentFile $assignmentFile,
                          User           $user,
                          Score          $score): array
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $question_id = $request->question_id;
        $student_user_id = $request->user_id;
        $assignment = $Assignment->find($assignment_id);

        $authorized = Gate::inspect('storeScore', [$assignmentFile, $user->find($student_user_id), $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $data = $request->validated();

        $extra_validation_response = $this->_extraValidations($request, $question_id, $assignment_id, $student_user_id);
        if ($extra_validation_response['type'] !== 'success') {
            return $extra_validation_response;
        }

        try {
            $text_feedback = $request->textFeedback ? trim($request->textFeedback) : '';
            DB::beginTransaction();
            DB::table('submission_files')
                ->where('user_id', $student_user_id)
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->update(['text_feedback' => $text_feedback,
                    'text_feedback_editor' => $data['text_feedback_editor'],
                    'date_graded' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'grader_id' => Auth::user()->id]);
            if ($request->file_submission_score !== null) {
                DB::table('submission_files')
                    ->where('user_id', $student_user_id)
                    ->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->update(['score' => $data['file_submission_score'],
                        'date_graded' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'grader_id' => Auth::user()->id]);
            }

            if ($request->question_submission_score !== null) {
                DB::table('submissions')
                    ->where('user_id', $student_user_id)
                    ->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->update(['score' => $data['question_submission_score'],
                            'updated_at' => Carbon::now()
                        ]
                    );
            }

            $score->updateAssignmentScore($student_user_id, $assignment_id, $assignment->assessment_type);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The score and feedback have been updated.';
            $response['grader_name'] = Auth::user()->first_name . ' ' . Auth::user()->last_name;
            $response['date_graded'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone);
        } catch (Exception $e) {

            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save the information.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param GradingRequest $request
     * @param int $question_id
     * @param int $assignment_id
     * @param int $student_user_id
     * @return array
     */
    private function _extraValidations(GradingRequest $request, int $question_id, int $assignment_id, int $student_user_id): array
    {
        $response['type'] = 'error';
        $max_points = DB::table('assignment_question')
            ->where('question_id', $question_id)
            ->where('assignment_id', $assignment_id)
            ->first()
            ->points;
        $submitted_total_score = 0;
        $submitted_total_score += $request->question_submission_score !== null
            ? $request->question_submission_score
            : 0;
        $submitted_total_score += $request->file_submission_score !== null
            ? $request->file_submission_score
            : 0;
        if ($submitted_total_score > $max_points) {
            $response['message'] = "The total of your Auto-Graded Score and Open-Ended Submission score can't be greater than the total number of points for this question.";
            return $response;
        }

        $current_file_submission = DB::table('submission_files')
            ->where('user_id', $student_user_id)
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->first();

        $current_question_submission = DB::table('submissions')
            ->where('user_id', $student_user_id)
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->first();

        if ($current_question_submission && $request->question_submission_score === null) {
            $response['message'] = "You can't submit an empty score for the auto-graded submission.";
            return $response;
        }

        if ($current_file_submission && $request->file_submission_score === null) {
            $response['message'] = "You can't submit an empty score for the open-ended submission.";
            return $response;
        }


        $current_file_submission_score = $current_file_submission->score ?? null;
        $current_text_feedback = $current_file_submission->text_feedback ?? '';
        if ($current_file_submission_score !== null) {
            $current_file_submission_score = 0 + Helper::removeZerosAfterDecimal($current_file_submission_score);

        }
        $current_question_submission_score = $current_question_submission->score ?? null;
        if ($current_question_submission_score !== null) {
            $current_question_submission_score = 0 + Helper::removeZerosAfterDecimal($current_question_submission_score);
        }
//keeping as == because too confusing with null, ''

        if ($current_file_submission_score == $request->file_submission_score
            && $current_question_submission_score == $request->question_submission_score
        && $current_text_feedback == $request->textFeedback) { // == in case of null vs ''
            $response['type'] = 'info';
            $response['message'] = "Nothing was updated.";
            return $response;
        }
        $response['type'] = 'success';
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param int $sectionId
     * @param string $gradeView
     * @param SubmissionFile $submissionFile
     * @param Enrollment $enrollment
     * @param Submission $Submission
     * @return array
     * @throws Exception
     */
    public function index(Assignment     $assignment,
                          Question       $question,
                          int            $sectionId,
                          string         $gradeView,
                          SubmissionFile $submissionFile,
                          Enrollment     $enrollment,
                          Submission     $Submission): array
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('viewAssignmentFilesByAssignment', [$submissionFile, $assignment, $sectionId]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $course = $assignment->course;
            $role = Auth::user()->role;

            $enrolled_users = $enrollment->getEnrolledUsersByRoleCourseSection($role, $course, $sectionId);
            $ferpa_mode = (int)request()->cookie('ferpa_mode') === 1 && Auth::user()->id === 5;
            if ($ferpa_mode) {
                $faker = \Faker\Factory::create();
                foreach ($enrolled_users as $key => $user) {
                    $enrolled_users[$key]['first_name'] = $faker->firstName;
                    $enrolled_users[$key]['last_name'] = $faker->lastName;
                }
            }

            if ($role === 4 && $sectionId === 0) {
                $access_level_override = $assignment->graders()
                    ->where('assignment_grader_access.user_id', Auth::user()->id)
                    ->first();
                if ($access_level_override && $access_level_override->pivot->access_level) {
                    $enrolled_users = $course->enrolledUsers;
                }
            }


            $user_ids = [];
            foreach ($enrolled_users as $user) {

                $user_ids[] = $user->id;
            }
            sort($user_ids);

            $submission_files_by_user = [];
            $submissions_by_user = [];
            $submission_files = $enrolled_users->isNotEmpty() ? $submissionFile->getUserAndQuestionFileInfo($assignment, $gradeView, $enrolled_users, $question->id) : [];
            if ($submission_files) {
                $submission_files = $submission_files[0];//comes back as an array of an array
            }
            foreach ($submission_files as $submission_file) {
                $submission_files_by_user[$submission_file['user_id']] = $submission_file;
            }
            $submissions = $enrolled_users->isNotEmpty() ? $Submission->getAutoGradedSubmissionsByUser($enrolled_users, $assignment, $question) : [];
            foreach ($submissions as $submission) {
                $submissions_by_user[$submission['user_id']] = $submission;
            }
            $grading = [];

            foreach ($enrolled_users as $user) {
                if ($submission_files_by_user[$user->id]['submission_status'] === $gradeView || $gradeView === 'allStudents') {
                    $grading[$user->id] = [];
                    $grading[$user->id]['student'] = [
                        'name' => "$user->first_name $user->last_name",
                        'user_id' => $user->id];
                    $grading[$user->id]['open_ended_submission'] = $submission_files_by_user[$user->id] ?? false;
                    $grading[$user->id]['auto_graded_submission'] = $submissions_by_user[$user->id] ?? false;
                }
            }

            $is_auto_graded = $question->technology_iframe !== '';
            $is_open_ended = DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->first()
                    ->open_ended_submission_type !== '0';
            $response['is_auto_graded'] = $is_auto_graded;
            $response['is_open_ended'] = $is_open_ended;
            $response['type'] = 'success';
            $response['grading'] = array_values($grading);
            $response['message'] = "Your view has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the file submissions for this assignment.  Please try again or contact us for assistance.";
        }

        return $response;

    }

}
