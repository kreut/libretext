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
     * @param $date
     * @param string $format
     * @return bool
     */
    function validateDate($date, string $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    /**
     * @param Request $request
     * @return LearningOutcome[]|\Illuminate\Database\Eloquent\Collection|string
     */
    public function learningOutcomes(Request $request)
    {
        if (Helper::isAdmin() || ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token'))) {
            return LearningOutcome::select('id', 'subject', 'topic', 'description')->get();
        } else {
            return 'Not authorized.';
        }
    }

    /**
     * @param Request $request
     * @return Collection|string
     */
    public function questionLearningOutcome(Request $request)
    {
        if (Helper::isAdmin() || ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token'))) {
            return DB::table('question_learning_outcome')->select('question_id', 'learning_outcome_id')->get();
        } else {
            return 'Not authorized.';
        }
    }

    /**
     * @param Analytics $analytics
     * @param Course $course
     * @param Assignment $assignment
     * @param Submission $submission
     * @param Enrollment $enrollment
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

    public
    function scoresByCourse(Request $request, Course $course, Score $score): array
    {

        //curl -H  "Authorization:Bearer <token>" https://adapt.libretexts.org/api/analytics/scores/course/{course}
        if ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token')) {
            $course_scores = $score->getCourseScores($course, 0);

            $download_rows = $course_scores['download_rows'];
            $download_fields = $course_scores['download_fields'];
            usort($download_rows, function ($a, $b) {
                return $a[0] <=> $b[0];
            });
            array_unshift($download_rows, $download_fields);

            $z_score_key = '';
            foreach ($download_rows[0] as $key => $value) {
                if ($value === 'Z-Score') {
                    $z_score_key = $key;
                }
            }
            if (!$z_score_key) {
                return ['error' => "Could not find the z-score."];
            }
            $analytics_info = [];
            foreach ($download_rows as $key => $download_row) {
                foreach ($download_row as $download_row_key => $value) {
                    if (!in_array($download_row_key, [0, 1, 2, 3, 4, 6, $z_score_key])) {
                        $analytics_info[$key][] = $value;
                    }

                }
            }
            return $analytics_info;
        } else {
            return ['error' => "Not authorized."];
        }

    }

    /**
     * @param Request $request
     * @param Course $course
     * @return Collection|string
     */
    public
    function proportionCorrectByAssignment(Request $request, Course $course)
    {

        //curl -k -H  "Authorization:Bearer <token>" https://local.adapt:8890/api/analytics/proportion-correct-by-assignment/course/415
        //curl -H  "Authorization:Bearer <token>" https://adapt.libretexts.org/api/analytics/proportion-correct-by-assignment/course/415
        if ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token')) {
            $assignment_ids = $course->assignments->pluck('id')->toArray();
            $scores = DB::table('scores')
                ->join('assignments', 'scores.assignment_id', '=', 'assignments.id')
                ->join('users', 'scores.user_id', '=', 'users.id')
                ->whereIn('assignment_id', $assignment_ids)
                ->select('assignment_id', 'email', 'score', 'users.id AS user_id', 'first_name', 'last_name', 'name')
                ->get();
            $randomizations = DB::table('assignments')->
            whereIn('id', $assignment_ids)
                ->whereNotNull('number_of_randomized_assessments')
                ->get();

            $assignment_question_num_questions = DB::table('assignment_question')
                ->groupBy('assignment_id')
                ->selectRaw('count(*) as count, assignment_id')
                ->whereIn('assignment_id', $assignment_ids)
                ->pluck('count', 'assignment_id');

            $assignment_question_points = DB::table('assignment_question')
                ->groupBy('assignment_id')
                ->selectRaw('sum(points) as sum, assignment_id')
                ->whereIn('assignment_id', $assignment_ids)
                ->pluck('sum', 'assignment_id');

            foreach ($assignment_question_num_questions as $assignment_id => $num_questions) {
                if (isset($randomizations[$assignment_id])) {
                    $proportion_answered_by_student = $assignment_question_num_questions[$assignment_id] > 0
                        ? $randomizations[$assignment_id] / $assignment_question_num_questions[$assignment_id]
                        : 0;
                    $assignment_question_points[$assignment_id] = $proportion_answered_by_student * $assignment_question_points[$assignment_id];
                }
            }
            foreach ($scores as $key => $value) {
                $assignment_id = $value->assignment_id;
                $scores[$key]->proportion_correct = $assignment_question_points[$assignment_id] > 0
                    ? Helper::removeZerosAfterDecimal(round($value->score / $assignment_question_points[$assignment_id], 4))
                    : 0;
                unset($scores[$key]->user_id);
                unset($scores[$key]->score);
            }
            return $scores;
        } else {
            return
                'Not authorized to get proportion correct.';
        }
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @return Collection|string
     */
    public
    function getReviewHistoryByAssignment(Request $request, Assignment $assignment)
    {
        //curl -H  "Authorization:Bearer <token>" https://adapt.libretexts.org/api/analytics/review-history/assignment/{assignment}

        if (($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token'))) {
            return DB::table('review_histories')
                ->join('users', 'review_histories.user_id', '=', 'users.id')
                ->select('users.email',
                    'review_histories.assignment_id',
                    'review_histories.question_id',
                    'review_histories.created_at',
                    'review_histories.updated_at')
                ->where('assignment_id', $assignment->id)
                ->get();
        } else {
            return
                'Not authorized.';
        }
    }

    public
    function index(Request $request, string $start_date = '', string $end_date = '')
    {
        /*curl -H  "Authorization:Bearer <token>" https://dev.adapt.libretexts.org/api/analytics -o analytics.zip
        Couldn't get this to work on staging (Internal Server error) so moved to dev*/

        if (Helper::isAdmin() || ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token'))) {
            if ($start_date) {
                if ($invalid_date = $this->invalidDate($start_date, $end_date, 7)) {
                    return $invalid_date;
                }
                $query_by_date = DB::table('data_shops')
                    ->where('time', '>=', $start_date)
                    ->where('time', '<=', $end_date)->get();
                return json_encode($query_by_date);
            }
            return Storage::disk('backup_s3')->get('analytics.zip');
        } else {
            return
                'Not authorized.';
        }

    }

    /**
     * @param Request $request
     * @param string $start_date
     * @param string $end_date
     * @return false|string
     */
    public
    function enrollments(Request $request, string $start_date = '', string $end_date = '')
    {
        /*curl -H  "Authorization:Bearer <token>" https://dev.adapt.libretexts.org/api/analytics
        Couldn't get this to work on staging (Internal Server error) so moved to dev*/
        $query = DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->select('email', 'course_id AS class', 'enrollments.created_at')
            ->where('users.fake_student', 0);
        if ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token')) {
            if ($start_date) {
                if ($invalid_date = $this->invalidDate($start_date, $end_date)) {
                    return $invalid_date;
                }
                $query = $query
                    ->where('enrollments.created_at', '>=', $start_date)
                    ->where('enrollments.created_at', '<=', $end_date);

            }
            return json_encode($query->get());
        } else {
            return
                'Not authorized.';
        }

    }

    /**
     * @param $start_date
     * @param $end_date
     * @param bool $max_diff
     * @return false|string
     */
    public
    function invalidDate($start_date, $end_date, bool $max_diff = false)
    {
        if (!$this->validateDate($start_date)) {
            return "$start_date is not of the form YYY-mm-dd.";
        }
        if (!$end_date) {
            return "You need an end date.";
        }
        if (!$this->validateDate($end_date)) {
            return "$end_date is not of the form YYY-mm-dd.";
        }

        if ($start_date > $end_date) {
            return "Your start date should be before your end date.";
        }
        $start_date = Carbon::parse($start_date);
        $end_date = Carbon::parse($end_date);

        if ($max_diff) {
            $diff = $start_date->diffInDays($end_date);
            if ($diff > $max_diff) {
                return "Max difference between start and end dates is 7 days.";
            }
        }
        return false;
    }

    private function _getNursingCourseInfo($course_id)
    {
        return Course::find($course_id)->join('users', 'courses.user_id', '=', 'users.id')
            ->select('courses.name', DB::raw('CONCAT(first_name, " " , last_name) AS instructor'))
            ->where('courses.id', $course_id)
            ->first();
    }
}
