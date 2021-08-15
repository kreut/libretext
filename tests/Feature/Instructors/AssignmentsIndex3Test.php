<?php

namespace Tests\Feature\Instructors;

use App\AssignToGroup;
use App\AssignToTiming;
use App\AssignToUser;
use App\Course;
use App\Enrollment;
use App\FinalGrade;
use App\Grader;
use App\LearningTree;
use App\Question;
use App\RandomizedAssignmentQuestion;
use App\Section;
use App\SubmissionFile;
use App\User;
use App\Assignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Traits\Test;

class AssignmentsIndex3Test extends TestCase
{
    use Test;

    /**Still must test the stuff with the correct/completed and number**/
    /** Should test that only an instructor can create an assignment... */
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;

        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;
        $this->student_user_ids = [$this->student_user->id, $this->student_user_2->id];
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);

        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id);
        $this->assign_tos = [
            [
                'groups' => [['value' => ['course_id' => $this->course->id], 'text' => 'Everybody']],
                'available_from' => '2020-06-10 09:00:00',
                'available_from_date' => '2020-06-10',
                'available_from_time' => '09:00:00',
                'due' => '2020-06-12 09:00:00',
                'due_date' => '2020-06-12',
                'due_time' => '09:00:00',
                'final_submission_deadline' => '2021-06-12 09:00:00',
                'final_submission_deadline_date' => '2021-06-12',
                'final_submission_deadline_time' => '09:00:00'
            ]
        ];
        $this->assignment_info = ['course_id' => $this->course->id,
            'name' => 'First Assignment',
            'assign_tos' => $this->assign_tos,
            'scoring_type' => 'p',
            'source' => 'a',
            'default_points_per_question' => 2,
            'students_can_view_assignment_statistics' => 0,
            'include_in_weighted_average' => 1,
            'late_policy' => 'not accepted',
            'assessment_type' => 'delayed',
            'default_open_ended_submission_type' => 'file',
            'instructions' => 'Some instructions',
            "number_of_randomized_assessments" => null,
            'notifications' => 1,
            'assignment_group_id' => 1,
            'file_upload_mode' => 'both'];

        foreach ($this->assign_tos[0]['groups'] as $key => $group) {
            $group_info = ["groups_$key" => ['Everybody'],
                "due_$key" => '2020-06-12 09:00:00',
                "due_date_$key" => '2020-06-12',
                "due_time_$key" => '09:00:00',
                "available_from_$key" => '2020-06-10',
                "available_from_date_$key" => '2020-06-12',
                "available_from_time_$key" => '09:00:00',
                "final_submission_deadline_date_$key" => '2021-06-12',
                "final_submission_deadline_time_$key" => '09:00:00'];
            foreach ($group_info as $info_key => $info_value) {
                $this->assignment_info[$info_key] = $info_value;
            }
        }


        $this->question = factory(Question::class)->create(['page_id' => 1]);


        $this->assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);

    }

    /** @test */
    public function delayed_assignment_cannot_switch_to_clicker_if_there_are_no_technology_questions()
    {
        DB::table('assignment_question')
            ->where('id',$this->assignment_question_id)
            ->update(['open_ended_submission_type' => 0]);

        $this->question->technology_iframe = '';
        $this->question->save();

        $this->assignment_info['assessment_type'] = 'clicker';
        $new_assessment_type = ucfirst( $this->assignment_info['assessment_type']);
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJson(['message' => "If you would like to change this assignment to $new_assessment_type, all of your assessments must have an associated auto-graded component H5P or Webwork.  Please remove any assessments that don't have auto-graded component."]);


    }

    /** @test */
    public function delayed_assignment_cannot_switch_to_learning_tree_assignment_if_it_has_assessments()
    {
        $this->assignment_info['assessment_type'] = 'learning tree';
        $this->assignment_info['min_time_needed_in_learning_tree'] = '5';
        $this->assignment_info['percent_earned_for_exploring_learning_tree'] = '90';
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJson(['message' => "You can't switch from a Delayed to a Learning Tree assessment type until you remove all current assessments."]);

    }

    /** @test */
    public function delayed_assignment_cannot_switch_to_clicker_if_there_are_open_ended_questions()
    {

        $this->assignment_info['assessment_type'] = 'clicker';
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJson(['message' => "If you would like to change this assignment to Clicker, please first remove any assessments that require an open-ended submission."]);


    }




    /**  @test */
    public function learning_tree_assignments_cannot_be_switched()
    {
      $this->assignment->assessment_type = 'learning tree';
      $this->assignment->save();

        $this->assignment_info['assessment_type'] = 'real time';
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJson(['message' => "This assignment already has non-Learning Tree assessments in it.  If you would like to change the assessment type, please first remove those assessments."]);

    }
}
