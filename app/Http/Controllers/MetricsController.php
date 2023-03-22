<?php

namespace App\Http\Controllers;

use App\Course;
use App\DataShop;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Metrics;
use App\Question;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MetricsController extends Controller
{
    /**
     * @param Metrics $metrics
     * @param int $download
     * @return array|void
     * @throws Exception
     */
    public function cellData(Metrics $metrics, int $download)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('cellData', $metrics);

        $rows = [];
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $cell_data_info = DataShop::select('data_shops.course_id',
                'data_shops.course_name',
                'schools.name as school_name', 'instructor_name', 'course_start_date')
                ->join('schools', 'data_shops.school', '=', 'schools.id')
                ->whereNotNull('data_shops.course_name')
                ->where('instructor_name', '<>', 'Instructor Kean')
                ->groupBy(['data_shops.course_id', 'data_shops.course_name', 'school_name', 'instructor_name', 'course_start_date'])
                ->orderBy('course_start_date', 'desc')
                ->get();
            $course_ids = [];
            $cell_data = [];
            foreach ($cell_data_info as $key => $data) {
                if (!in_array($data->course_id, $course_ids)) {
                    $cell_data[] = $data;
                    $course_ids[] = $data->course_id;
                }
            }
            $total_entries_by_course = DataShop::select('course_id', DB::raw('COUNT(DISTINCT anon_student_id) as total_entries'))
                ->groupBy('course_id')
                ->get();
            $total_entries_by_course_id = [];
            foreach ($total_entries_by_course as $entry) {
                $total_entries_by_course_id[$entry->course_id] = $entry->total_entries;
            }
            foreach ($cell_data as $key => $data) {
                if ($total_entries_by_course_id[$data->course_id] < 3) {
                    unset($cell_data[$key]);
                } else {
                    $cell_data[$key]['number_of_enrolled_students'] = $total_entries_by_course_id[$data->course_id];
                    $cell_data[$key]['term'] = $this->_getTerm($data['course_start_date']);
                }
            }

            $cell_data = array_values($cell_data);
            $response['type'] = 'success';
            $response['cell_data'] = $cell_data;
            if ($download) {
                $columns = ['Course Name', 'Term', 'School Name', 'Instructor Name', 'Number of Enrolled Students'];
                $rows[0] = $columns;
                $keys = ['course_name', 'term', 'school_name', 'instructor_name', 'number_of_enrolled_students'];
                foreach ($cell_data as $data) {
                    $values = [];
                    foreach ($keys as $key) {
                        $values[] = $data->{$key};
                    }
                    $rows[] = $values;
                }
                $date = Carbon::now()->format('Y-m-d');
                Helper::arrayToCsvDownload($rows, "cell-data-$date");
            }


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the Cell Data.  Please try again.";
        }

        if (!$download) {
            return $response;
        }

    }

    private function _getMyId()
    {
        if (app()->environment() === 'testing') {
            return 100;
        }
        return DB::table('users')->where('first_name', 'Instructor')->where('last_name', 'Kean')->first()->id;
    }

    /**
     * @param User $user
     * @param Question $question
     * @param Metrics $metrics
     * @param int $download
     * @return array|void
     * @throws Exception
     */
    public function index(User $user, Question $question, Metrics $metrics, int $download)
    {
        $my_id = $this->_getMyId();


        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $metrics);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $instructor_accounts = $user->where('role', 2)->where('id', '<>', $my_id)->count();
            $student_accounts = $user->where('role', 3)->where('fake_student', 0)->count();
            $questions = $question->count();
            $campuses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('users.id', '<>', $my_id)
                ->select('school_id')
                ->groupBy('school_id')
                ->count();
            $courses = DB::table('data_shops')
                ->where('instructor_name', '<>', 'Instructor Kean')
                ->select('course_id')
                ->groupBy('course_id')
                ->get();
            $total_entries_by_course = DataShop::select('course_id', DB::raw('COUNT(DISTINCT anon_student_id) as total_entries'))
                ->groupBy('course_id')
                ->get();
            $total_entries_by_course_id = [];
            foreach ($total_entries_by_course as $entry) {
                $total_entries_by_course_id[$entry->course_id] = $entry->total_entries;
            }
            foreach ($courses as $key => $data) {
                if ($total_entries_by_course_id[$data->course_id] < 3) {
                    unset($courses[$key]);
                }
            }
            $real_courses = count($courses);

            $live_courses = Course::select('courses.name', 'users.first_name', 'users.last_name')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->whereIn('courses.id', function ($query) {
                    $query->select('course_id')
                        ->from('users')
                        ->join('enrollments', 'users.id', '=', 'enrollments.user_id')
                        ->where([
                            ['users.fake_student', 0],
                            ['users.last_name', '<>', 'Kean'],
                        ])
                        ->groupBy('course_id');
                })->count();


            $grade_passbacks = DB::table('lti_grade_passbacks')->max('id');
            $LTI_schools = DB::table('lti_registrations')->count();
            $open_ended_submissions = DB::table('submission_files')->max('id');
            $auto_graded_submissions = DB::table('data_shops')->
            join('users', 'data_shops.anon_student_id', '=', 'users.email')
                ->where('fake_student', 0)->count();
            $metrics = compact('instructor_accounts',
                'student_accounts',
                'questions',
                'campuses',
                'real_courses',
                'live_courses',
                'grade_passbacks',
                'LTI_schools',
                'open_ended_submissions',
                'auto_graded_submissions');
            $response['metrics'] = $metrics;
            $response['type'] = 'success';
            if ($download) {
                $rows = [];
                foreach ($metrics as $key => $metric) {
                    $rows[] = [ucwords(str_replace('_', ' ', $key)), $metric];
                }

                $date = Carbon::now()->format('Y-m-d');
                Helper::arrayToCsvDownload($rows, "metrics-$date");

            }

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the metrics.  Please try again.";

        }
        if (!$download) {
            return $response;
        }
    }

    private function _getTerm($datetime)
    {

        $carbon_datetime = Carbon::createFromFormat('Y-m-d H:i:s', $datetime);

        if ($carbon_datetime->month >= 3 && $carbon_datetime->month <= 5) {
            $season = "Spring";
        } elseif ($carbon_datetime->month >= 6 && $carbon_datetime->month <= 8) {
            $season = "Summer";
        } elseif ($carbon_datetime->month >= 9 && $carbon_datetime->month <= 11) {
            $season = "Fall";
        } else {
            $season = "Winter";
        }
        return $season . ' ' . $carbon_datetime->format('Y');
    }

}
