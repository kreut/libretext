<?php

namespace App\Console\Commands\Analytics;

use App\Course;
use App\DataShop;
use App\Exceptions\Handler;
use App\Question;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class cacheMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:Metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Question $question
     * @param User $user
     * @return int
     * @throws Exception
     */
    public function handle(Question $question, User $user)
    {
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
            Cache::forever('cell_data', $cell_data);
            echo "Cell data cached.\r\n";

            $my_id = $this->_getMyId();
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
            $auto_graded_submissions = DB::table('submissions')->max('id');
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
            Cache::forever('metrics', $metrics);
            echo "Metrics cached.\r\n";
            return 0;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

        }
        echo $e->getMessage();
        return 1;
    }

    /**
     * @param $datetime
     * @return string
     */
    private function _getTerm($datetime): string
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

    /**
     * @return int|mixed
     */
    private function _getMyId()
    {
        if (app()->environment() === 'testing') {
            return 100;
        }
        return DB::table('users')->where('first_name', 'Instructor')->where('last_name', 'Kean')->first()->id;
    }
}
