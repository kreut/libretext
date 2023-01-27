<?php

namespace App\Http\Controllers;

use App\Course;
use App\DataShop;
use App\Exceptions\Handler;
use App\Question;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function cellData(DataShop $dataShop)
    {
        $response['type'] = 'error';
        try {
            $cell_data = DataShop::select('data_shops.course_id', 'data_shops.course_name', 'schools.name as school_name', 'instructor_name')
                ->join('schools', 'data_shops.school', '=', 'schools.id')
                ->whereNotNull('data_shops.course_name')
                ->where('instructor_name','<>','Instructor Kean')
                ->groupBy(['data_shops.course_id', 'data_shops.course_name', 'school_name', 'instructor_name'])
                ->get();
            $response['type'] = 'success';
            $response['cell_data'] = $cell_data;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the Cell Data.  Please try again.";
        }
        return $response;
    }

    private function _getMyId()
    {
        return DB::table('users')->where('first_name', 'Instructor')->where('last_name', 'Kean')->first()->id;
    }

    /**
     * @param User $user
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public function index(User $user, Question $question): array
    {
        $my_id = $this->_getMyId();

        $response['type'] = 'error';

        try {
            $instructor_accounts = $user->where('role', 2)->where('id', '<>', $my_id)->count();
            $student_accounts = $user->where('role', 3)->where('fake_student', 0)->count();
            $questions = $question->count();
            $campuses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('users.id', '<>', $my_id)
                ->select('school_id')->groupBy('school_id')->count();
            $real_courses = DB::table('data_shops')->groupBy('course_id')->count();
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
            $instructor_accounts_with_students = 'todo';
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
                'instructor_accounts_with_students',
                'open_ended_submissions',
                'auto_graded_submissions');
            $response['metrics'] = $metrics;
            $response['type'] = 'success';


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the metrics.  Please try again.";

        }
        return $response;

    }
}
