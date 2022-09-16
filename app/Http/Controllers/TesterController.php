<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignToUser;
use App\Course;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Extension;
use App\ExtraCredit;
use App\Http\Requests\StoreTester;
use App\LtiGradePassback;
use App\Score;
use App\Section;
use App\Seed;
use App\Submission;
use App\SubmissionFile;
use App\TesterCourse;
use App\TesterStudent;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Snowfire\Beautymail\Beautymail;

class TesterController extends Controller
{
    /**
     * @param Request $request
     * @param Course $course
     * @param User $tester
     * @param Enrollment $enrollment
     * @param TesterCourse $testerCourse
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
     * @return array
     * @throws Exception
     */
    public function destroy(Request          $request,
                            Course           $course,
                            User             $tester,
                            Enrollment       $enrollment,
                            TesterCourse     $testerCourse,
                            AssignToUser     $assignToUser,
                            Assignment       $assignment,
                            Submission       $submission,
                            SubmissionFile   $submissionFile,
                            Score            $score,
                            Extension        $extension,
                            LtiGradePassback $ltiGradePassback,
                            Seed             $seed,
                            ExtraCredit      $extraCredit,
                            Section          $section): array
    {

        $authorized = Gate::inspect('destroy', [$testerCourse, $tester, $course]);

        $response['type'] = 'error';
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $remove_option = $request->removeOption;

        try {
            switch ($request->removeOption) {
                case('remove-associated-students'):
                    $students = DB::table('tester_courses')
                        ->join('tester_students', 'tester_user_id', '=', 'tester_students.tester_user_id')
                        ->join('sections', 'tester_students.section_id', '=', 'sections.id')
                        ->where('sections.course_id', $course->id)
                        ->select('tester_students.student_user_id', 'tester_students.section_id')
                        ->get();
                    DB::beginTransaction();
                    foreach ($students as $student) {
                        $user = User::find($student->student_user_id);
                        if (!$user->testing_student){
                            $response['message'] = "You cannot remove this tester because one of their students is not a testing students.  Please contact support.";
                            return $response;
                        }
                        $Section = $section->where('id', $student->section_id)->first();
                        $enrollment->removeAllRelatedEnrollmentInformation($user,
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
                        DB::table('tester_students')->where('student_user_id', $user->id)->delete();
                        DB::table('users')->where('id', $user->id)->delete();
                    }
                    $testerCourse->where('user_id', $tester->id)
                        ->where('course_id', $course->id)
                        ->delete();
                    DB::commit();
                    break;
                case('maintain-student-information'):
                    $testerCourse->where('user_id', $tester->id)
                        ->where('course_id', $course->id)
                        ->delete();
                    break;
                default:
                    $response['message'] = "$remove_option is not a valid option.";
                    return $response;

            }
            $response['type'] = 'success';
            $tester = User::find($tester->id);
            $response['message'] = "$tester->first_name $tester->last_name has been removed as a tester.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the tester.  Please refresh your page and try again.";
        }
        return $response;


    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function index(Course $course): array
    {

        $authorized = Gate::inspect('getTesters', $course);

        $response['type'] = 'error';
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $testers = DB::table('tester_courses')
                ->join('users', 'tester_courses.user_id', '=', 'users.id')
                ->where('tester_courses.course_id', $course->id)
                ->select('user_id', 'email', DB::raw('CONCAT(first_name, " ", last_name) AS name'))
                ->get();
            $response['testers'] = $testers;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the testers for this course.  Please refresh your page and try again.";
        }
        return $response;


    }

    /**
     * @param StoreTester $request
     * @param TesterCourse $testerCourse
     * @return array
     * @throws Exception
     */
    public function store(StoreTester $request, TesterCourse $testerCourse): array
    {
        $response['type'] = 'error';
        $course = Course::find($request->course_id);
        if (!$course) {
            $response['message'] = "That is not a valid course.";
            return $response;
        }
        $authorized = Gate::inspect('storeTester', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $data = $request->validated();
            $testerCourse->user_id = DB::table('users')->where('email', trim($data['email']))->first()->id;
            $testerCourse->course_id = $request->course_id;
            $testerCourse->save();
            $response['type'] = 'success';
            $response['message'] = 'The tester has been added to your course.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding the tester.  Please refresh your page and try again.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param User $student
     * @param TesterStudent $testerStudent
     * @return array
     * @throws Exception
     */
    public function emailResults(Request $request, User $student, TesterStudent $testerStudent): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('emailResults', [$testerStudent, $student]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $submissions = DB::table('submissions')->where('user_id', $student->id)->get();
            $student = User::find($student->id);
            $course_info = DB::table('tester_students')
                ->join('sections', 'tester_students.section_id', '=', 'sections.id')
                ->join('courses', 'sections.course_id', '=', 'courses.id')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('tester_students.student_user_id', $student->id)
                ->select('courses.name', 'users.first_name', 'users.last_name')
                ->first();
            $score = 0;
            foreach ($submissions as $submission) {
                $score += $submission->score;
            }

            $beauty_mail = app()->make(Beautymail::class);
            $results_info = ['score' => $score,
                'number_of_responses' => count($submissions),
                'student' => "$student->first_name $student->last_name",
                'tester' => request()->user()->first_name . ' ' . request()->user()->last_name,
                'instructor_first_name' => $course_info->first_name,
                'course' => $course_info->name
            ];
            if (!app()->environment('local')) {
                $beauty_mail->send('emails.tester_student_results', $results_info, function ($message)
                use ($request) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->to(request()->user()->email)
                        ->subject('Score Results');
                });
            }
            $response['message'] = "$course_info->first_name $course_info->last_name has been emailed these results.";
            $response['results_info'] = $results_info;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error emailing the results.  Please refresh your page and try again.";
        }
        return $response;


    }
}
