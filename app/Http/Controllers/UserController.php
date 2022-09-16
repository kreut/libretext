<?php

namespace App\Http\Controllers;


use App\Assignment;
use App\AssignToUser;
use App\Course;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Extension;
use App\ExtraCredit;
use App\Http\Requests\UpdateStudentEmail;
use App\LtiGradePassback;
use App\School;
use App\Score;
use App\Section;
use App\Seed;
use App\Submission;
use App\SubmissionFile;
use App\TesterStudent;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * @param User $student
     * @param Course $course
     * @param AssignToUser $assignToUser
     * @param Assignment $assignment
     * @param Submission $submission
     * @param SubmissionFile $submissionFile
     * @param Score $score
     * @param Extension $extension
     * @param LtiGradePassback $ltiGradePassback
     * @param Seed $seed
     * @param ExtraCredit $extraCredit
     * @param Section $section
     * @param Enrollment $enrollment
     * @param TesterStudent $testerStudent
     * @return array
     * @throws Exception
     */
    public function destroy(User             $student,
                            Course           $course,
                            AssignToUser     $assignToUser,
                            Assignment       $assignment,
                            Submission       $submission,
                            SubmissionFile   $submissionFile,
                            Score            $score,
                            Extension        $extension,
                            LtiGradePassback $ltiGradePassback,
                            Seed             $seed,
                            ExtraCredit      $extraCredit,
                            Section          $section,
                            Enrollment       $enrollment,
                            TesterStudent    $testerStudent): array
    {

        $authorized = Gate::inspect('destroy', $student);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $name = "$student->first_name $student->last_name";
            $section_id = $testerStudent->where('student_user_id', $student->id)->first()->section_id;
            $Section = $section->where('id', $section_id)->first();
            DB::beginTransaction();
            $enrollment->removeAllRelatedEnrollmentInformation($student,
                $course->id,
                $assignToUser,
                $assignment,
                $submission,
                $submissionFile,
                $score,
                $extension,
                $ltiGradePassback,
                $seed,
                $extraCredit,
                $Section);
            DB::table('tester_students')->where('student_user_id', $student->id)->delete();
            DB::table('users')->where('id', $student->id)->delete();
            DB::commit();
            $response['type'] = 'info';
            $response['message'] = "$name has been removed from the system.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We are unable to delete $name from the system.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param UpdateStudentEmail $request
     * @param User $student
     * @return array
     * @throws Exception
     */
    public function updateStudentEmail(UpdateStudentEmail $request, User $student): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('updateStudentEmail', [$request->user(), $student->id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $data = $request->validated();
            $student->email = $data['email'];
            $student->save();
            $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
            $to_email = $data['email'];

            $email_info = ['student_first_name' => $student->first_name,
                'instructor_name' => $request->user()->first_name . ' ' . $request->user()->last_name
            ];
            if (!app()->environment('testing')) {
                $beauty_mail->send('emails.email_changed', $email_info, function ($message)
                use ($to_email) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->to($to_email)
                        ->subject("ADAPT email changed");
                });
            }

            $response['type'] = 'success';
            $response['message'] = "$student->first_name $student->last_name's email has updated and they have been notified by email.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating this student's email. Please try again or contact us.";

        }
        return $response;
    }

    public
    function getAllQuestionEditors(User $user): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAllQuestionEditors', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $response['question_editors'] = DB::table('users')
                ->select('users.id AS value', DB::raw('CONCAT(first_name, " " , last_name) AS label'))
                ->orderBy('label')
                ->whereIn('role', [2, 5])
                ->where('id', '<>', $user->id)
                ->get();

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not get all the potential question owners.';
        }
        return $response;


    }

    /**
     * @param User $user
     * @return array
     * @throws Exception
     */
    public
    function setAnonymousUserSession(User $user): array
    {
        $response['type'] = 'error';


        $authorized = Gate::inspect('setAnonymousUserSession', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            session()->put('anonymous_user', true);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not set you as an anonymous user.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param Request $request
     * @param User $user
     * @param School $school
     * @return array
     * @throws Exception
     */
    public
    function getInstructorsWithPublicCourses(Request $request, User $user, School $school): array
    {

        $school_id = $request->name
            ? $school->where('name', $request->name)
                ->first()
                ->id
            : 0;

        try {
            $instructors = DB::table('users')
                ->join('courses', 'users.id', '=', 'courses.user_id')
                ->where('users.role', 2)
                ->where('public', 1);

            if ($school_id) {
                $instructors = $instructors->where('courses.school_id', $school_id);
            }
            $instructors = $instructors->groupBy('users.id')
                ->orderBy('users.last_name')
                ->select('user_id', DB::raw("CONCAT(first_name, ' ',last_name) AS name"))
                ->get();

            $response['type'] = 'success';
            $response['instructors'] = $instructors;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the instructors.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
