<?php

namespace App\Http\Controllers;

use App\Analytics;
use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\LearningOutcome;
use App\Score;
use App\Submission;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AnalyticsController extends Controller
{


    /**
     * @param int $download
     * @param Analytics $analytics
     * @param Course $course
     * @param Assignment $assignment
     * @param Submission $submission
     * @return array
     * @throws Exception
     */
    public function nursing(int        $download,
                            Analytics  $analytics,
                            Course     $course,
                            Assignment $assignment,
                            Submission $submission): array
    {
        $response['type'] = 'error';
        $nursing_user_id = 6314;
        $authorized = Gate::inspect('nursing', [$analytics, $nursing_user_id]);
        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            /*students/users using the ADAPT assignments in the Next Gen RN account and time spent on the assignments.*/
            $nursing_formative_course_ids = $course->where('user_id', $nursing_user_id)
                ->where('formative', 1)
                ->get()
                ->pluck('id')
                ->toArray();
            $nursing_formative_assignment_ids = $assignment->whereIn('course_id', $nursing_formative_course_ids)
                ->get()
                ->pluck('id')
                ->toArray();
            $nursing_formative_question_ids = DB::table('assignment_question')
                ->whereIn('assignment_id', $nursing_formative_assignment_ids)
                ->get()
                ->pluck('question_id')
                ->toArray();

            $nursing_summative_course_ids = $course->where('user_id', $nursing_user_id)
                ->where('formative', 0)
                ->get()
                ->pluck('id')
                ->toArray();
            $nursing_summative_assignment_ids = $assignment->whereIn('course_id', $nursing_summative_course_ids)
                ->get()
                ->pluck('id')
                ->toArray();
            $nursing_summative_question_ids = DB::table('assignment_question')
                ->whereIn('assignment_id', $nursing_summative_assignment_ids)
                ->get()
                ->pluck('question_id')
                ->toArray();

            $all_next_gen_formative_course_ids = DB::table('submissions')
                ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->whereIn('question_id', $nursing_formative_question_ids)
                ->select('courses.id')
                ->groupBy('courses.id')
                ->get('courses.id')
                ->pluck('id')
                ->toArray();

            $all_next_gen_summative_course_ids = DB::table('submissions')
                ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->whereIn('question_id', $nursing_summative_question_ids)
                ->select('courses.id')
                ->groupBy('courses.id')
                ->get('courses.id')
                ->pluck('id')
                ->toArray();

            $analytics = [];
            $download_row_data = [['Course', 'Instructor', 'Type', 'Number of Enrollments', 'Number of Submissions', 'Avg Time on Task']];
            foreach ($all_next_gen_formative_course_ids as $course_id) {
                $course = $this->_getNursingCourseInfo($course_id);

                $formative_assignment_ids = Course::find($course_id)->assignments->pluck('id')->toArray();
                $number_of_formative_submissions = $submission->whereIn('assignment_id', $formative_assignment_ids)
                    ->join('users', 'submissions.user_id', '=', 'users.id')
                    ->where('fake_student', 0)
                    ->count();

                if ($number_of_formative_submissions < 5) {
                    continue;
                }

                $highest_time_on_tasks = $this->_getHighestTimeOnTasks($formative_assignment_ids, $number_of_formative_submissions);

                $formative_avg_time_on_task = $submission->whereIn('assignment_id', $formative_assignment_ids)
                    ->join('users', 'submissions.user_id', '=', 'users.id')
                    ->where('fake_student', 0)
                    ->whereNotIn('submissions.id', $highest_time_on_tasks)
                    ->avg('time_on_task');


                $formative_avg_time_on_task = $this->_formatAvgTimeOnTask($formative_avg_time_on_task);
                $analytics[] = ['course' => $course->name,
                    'instructor' => $course->instructor,
                    'type' => 'formative',
                    'number_of_enrollments' => 'N/A',
                    'number_of_submissions' => $number_of_formative_submissions,
                    'avg_time_on_task' => $formative_avg_time_on_task];
                $download_row_data[] = [$course->name, $course->instructor, 'formative', 'N/A', $number_of_formative_submissions, $formative_avg_time_on_task];
            }


            foreach ($all_next_gen_summative_course_ids as $course_id) {

                $course = $this->_getNursingCourseInfo($course_id);

                $summative_assignment_ids = Course::find($course_id)->assignments->pluck('id')->toArray();

                $number_of_summative_submissions = $submission->whereIn('assignment_id', $summative_assignment_ids)
                    ->join('users', 'submissions.user_id', '=', 'users.id')
                    ->where('fake_student', 0)
                    ->count();
                if ($number_of_summative_submissions < 5) {
                    continue;
                }

                $number_of_summative_enrollments = DB::table('enrollments')
                    ->join('users', 'enrollments.user_id', '=', 'users.id')
                    ->where('fake_student', 0)
                    ->where('course_id', $course_id)
                    ->count();

                $summative_highest_time_on_tasks = $this->_getHighestTimeOnTasks($summative_assignment_ids, $number_of_summative_submissions);
                $summative_avg_time_on_task = $submission->whereIn('assignment_id', $summative_assignment_ids)
                    ->join('users', 'submissions.user_id', '=', 'users.id')
                    ->where('fake_student', 0)
                    ->whereNotIn('submissions.id', $summative_highest_time_on_tasks)
                    ->avg('time_on_task');

                $summative_avg_time_on_task = $this->_formatAvgTimeOnTask($summative_avg_time_on_task);
                $analytics[] = ['instructor' => $course->instructor,
                    'course' => $course->name,
                    'type' => 'summative',
                    'number_of_submissions' => $number_of_summative_submissions,
                    'number_of_enrollments' => $number_of_summative_enrollments,
                    'avg_time_on_task' => $summative_avg_time_on_task];
                $download_row_data[] = [$course->name, $course->instructor, 'summative', $number_of_summative_enrollments, $number_of_summative_submissions, $summative_avg_time_on_task];
            }
            if ($download) {
                Helper::arrayToCsvDownload($download_row_data, 'Analytics');
                exit;
            }
            $response['analytics'] = $analytics;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
        return $response;
    }

    /**
     * @param $avg_time_on_task
     * @return string
     */
    private function _formatAvgTimeOnTask($avg_time_on_task): string
    {
        $seconds = floor($avg_time_on_task) % 60;
        $minutes = floor($avg_time_on_task / 60);
        return $avg_time_on_task > 60 ? "$minutes min, $seconds sec" : "$seconds sec";
    }

    /**
     * @param $assignment_ids
     * @param $number_of_submissions
     * @return array
     */
    private
    function _getHighestTimeOnTasks($assignment_ids, $number_of_submissions): array
    {
        return DB::table('submissions')->whereIn('assignment_id', $assignment_ids)
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->where('fake_student', 0)
            ->orderBy('time_on_task', 'DESC')
            ->limit(.1 * $number_of_submissions)
            ->get('submissions.id')
            ->pluck('id')
            ->toArray();
    }


    private function _getNursingCourseInfo($course_id)
    {
        return Course::find($course_id)->join('users', 'courses.user_id', '=', 'users.id')
            ->select('courses.name', DB::raw('CONCAT(first_name, " " , last_name) AS instructor'))
            ->where('courses.id', $course_id)
            ->first();
    }
}
