<?php

namespace App;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class DataShop extends Model
{

    public $timestamps = false;
    public $guarded = [];

    /**
     * @param $datetime
     * @return string
     */
    public function getTerm($datetime): string
    {
        try {
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
        } catch (Exception $e) {
            return 'No term provided.';
        }
    }

    /**
     * @param string $type
     * @param $data
     * @param Assignment $assignment
     * @param $assignment_question
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function store(string $type, $data, Assignment $assignment, $assignment_question)
    {
        $extra_info = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->join('assignment_groups', 'assignments.assignment_group_id', '=', 'assignment_groups.id')
            ->select('users.email',
                'users.first_name',
                'users.last_name',
                'assignment_groups.assignment_group',
                'courses.textbook_url')
            ->where('assignments.id', $assignment->id)
            ->first();
        $assignment_points = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->sum('points');
        switch ($type) {
            case('submission'):
                $question = Question::find($data['submission']->question_id);
                $this->submission_time = Carbon::now();
                $this->submission_count = $data['submission']->submission_count;
                $this->outcome = $data['all_correct'] ? 'CORRECT' : 'INCORRECT';
                $this->session_id = session()->get('submission_id');
                $this->anon_student_id = Auth::user()->email ? Auth::user()->email : 'test';
                break;
            case('time_to_review'):
                $question = Question::find($data->question_id);
                $this->review_time_start = $data->created_at;
                $this->review_time_end = $data->updated_at;
                $this->session_id = $data->session_id;
                $this->anon_student_id = $data->email;
                break;
            default:
                throw new Exception ("$type is not a valid data_shop type.");

        }

        if ($this->session_id) {
            //only if session exists
            $this->assignment_id = $assignment->id;
            $this->assignment_name = $assignment->name;
            $this->assignment_group = $extra_info->assignment_group;
            $this->assignment_scoring_type = $assignment->scoring_type;
            $this->assignment_points = $assignment_points;
            $this->number_of_attempts_allowed = $assignment->assessment_type === 'delayed' ? 'unlimited' : '1';
            $this->question_id = $question->id;
            if ($type === 'submission') {
                $this->question_id .= $data['sub_content_id'] ? "-{$data['sub_content_id']}" : '';
            }
            if ($type === 'submission' && !$assignment_question) {
                throw new Exception ("Datashop has no assignment-question for $assignment->id with $question->id submitted by $this->anon_student_id.");
            }
            $this->question_points = $assignment_question ? $assignment_question->points : 'n/a';
            $this->library = $question->library;
            $this->page_id = $question->page_id;
            $this->question_url = $question->url;
            $this->textbook_url = $extra_info->textbook_url;
            if (!$assignment->course->formative) {
                $this->due = $assignment->assignToTimingByUser('due');
            }
            $this->school = $assignment->course->school_id;
            $this->course_id = $assignment->course->id;
            $this->course_name = $assignment->course->name;
            $this->course_start_date = $assignment->course->start_date;
            $this->instructor_name = "$extra_info->first_name $extra_info->last_name";
            $this->instructor_email = $extra_info->email;
            $this->updated_at = now();
            $this->save();
        }
        if ($assignment->course->formative) {
            $course = $assignment->course;
            $data_shops_enrollment = DB::table('data_shops_enrollments')
                ->where('course_id', $course->id)
                ->select('id', 'assignment_id', 'question_id', 'number_of_enrolled_students')
                ->first();
            if ($data_shops_enrollment) {
                if ($data_shops_enrollment->assignment_id === $assignment->id
                    && $data_shops_enrollment->question_id === $question->id) {
                  DB::table('data_shops_enrollments')
                      ->where('id', $data_shops_enrollment->id)
                        ->update([
                            'number_of_enrolled_students' => $data_shops_enrollment->number_of_enrolled_students + 1,
                            'updated_at' =>now()]);

                }
            } else {
                $extra_info = DB::table('courses')
                    ->join('schools', 'courses.school_id', '=', 'schools.id')
                    ->join('users', 'courses.user_id', '=', 'users.id')
                    ->where('courses.id', $course->id)
                    ->select('schools.name AS school_name', DB::raw('CONCAT(first_name, " " , last_name) AS instructor_name'))
                    ->first();
                $data = ['course_id' => $course->id,
                    'course_name' => $course->name,
                    'school_name' => $extra_info->school_name,
                    'instructor_name' => $extra_info->instructor_name,
                    'term' => $course->term,
                    'number_of_enrolled_students' => 1,
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'created_at' => now(),
                    'updated_at' => now()];
                DB::table('data_shops_enrollments')->insert($data);
            }

        }
    }

}
