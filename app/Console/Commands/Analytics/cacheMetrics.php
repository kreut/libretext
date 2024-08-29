<?php

namespace App\Console\Commands\Analytics;

use App\Course;
use App\DataShop;
use App\DataShopsComplete;
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
     * @param DataShop $dataShop
     * @return int
     * @throws Exception
     */
    public function handle(Question $question, User $user, DataShop $dataShop): int
    {
        try {
            $cell_data = DB::table('data_shops_enrollments')
                ->join('courses', 'data_shops_enrollments.course_id', '=', 'courses.id')
                ->leftJoin('disciplines', 'courses.discipline_id', '=', 'disciplines.id')
                ->where('instructor_name', '<>', 'Instructor Kean')
                ->select('data_shops_enrollments.*', 'disciplines.name AS discipline')
                ->get()
                ->toArray();
            $cell_data = array_values($cell_data);
            foreach ($cell_data as $key => $value) {
                if (!$value->discipline) {
                    $value->discipline = "None provided";
                }

            }
            Cache::forever('cell_data', $cell_data);
            echo "Cell data cached.\r\n";

            $my_id = $this->_getMyId();
            $instructor_accounts = $user->where('role', 2)->where('id', '<>', $my_id)->count();
            $student_accounts = $user->where('role', 3)
                ->where('fake_student', 0)
                ->where('email', 'regexp', '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$')
                ->count();
            $questions = $question->count();
            $campuses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('users.id', '<>', $my_id)
                ->select('school_id')
                ->groupBy('school_id')
                ->count();
            /* $courses = DB::table('data_shops_complete')
                 ->select('course_id', 'instructor_name')
                 ->distinct()
                 ->get();
             foreach ($courses as $key => $course) {
                 if ($course->instructor_name === 'Instructor Kean') {
                     $courses->forget($key);
                 }

             }
            /* $total_entries_by_course = DB::table('data_shops_complete')
                 ->select('course_id', DB::raw('COUNT(DISTINCT anon_student_id) as total_entries'))
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
            */

            $live_courses = $this->_liveCoursesQuery($my_id, [0,1]);
            $live_lms_courses = $this->_liveCoursesQuery($my_id, [1]);
            $live_non_lms_courses = $this->_liveCoursesQuery($my_id, [0]);

            $grade_passbacks = DB::table('lti_grade_passbacks')->max('id');
            $LTI_schools = DB::table('lti_registrations')
                ->join('lti_schools', 'lti_registrations.id', '=', 'lti_schools.lti_registration_id')
                ->count();
            $open_ended_submissions = DB::table('submission_files')->max('id');
            $auto_graded_submissions = DB::table('submissions')->max('id');
            $metrics = compact('instructor_accounts',
                'student_accounts',
                'questions',
                'campuses',
                'live_courses',
                'live_lms_courses',
                'live_non_lms_courses',
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

    private function _liveCoursesQuery(int $my_id, array $lms)
    {
        return Course::select('courses.name', 'users.first_name', 'users.last_name')
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->where('courses.user_id', '<>', $my_id)
            ->whereIN('courses.lms', $lms)
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
