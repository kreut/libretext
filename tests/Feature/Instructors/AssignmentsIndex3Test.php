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
use App\Score;
use App\Section;
use App\Submission;
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
            'points_per_question' => 'number of points',
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
    public function switching_from_number_of_points_to_question_weight_will_make_all_weights_1_and_equalize_the_points()
    {
        $this->assignment->points_per_question = 'number of points';
        $this->assignment->save();
        $this->question_2 = factory(Question::class)->create(['page_id' => 1214214123]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10,
            'order' => 1,
            'weight' => null,
            'open_ended_submission_type' => 'file'
        ]);
        $this->assignment_info['points_per_question'] = 'question weight';
        $this->assignment_info['total_points'] = 100;

        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJson(['type' => 'success']);

        $num_with_correct_weight_and_points = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('weight', 1)
            ->where('points', $this->assignment_info['total_points'] / 2) //2 questions with equal weight
            ->count();
        $this->assertEquals(2, $num_with_correct_weight_and_points);

    }



    /** @test */

    public function cannot_update_total_points_if_assignment_is_open()
    {
        DB::table('assign_to_timings')
            ->update([
                'available_from' => Carbon::yesterday(),
                'due' => Carbon::tomorrow()
            ]);
        $this->assignment->total_points = 20;
        $this->assignment->save();
        $this->assignment_info['points_per_question'] = "question weight";
        $this->assignment_info['total_points'] = 10;
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJsonValidationErrors('total_points');

    }

    /** @test */

    public function if_total_points_are_changed_everything_scales_correctly()
    {

        $submission_score = 4;
        $file_submission_score = 30;
        $assignment_score = 35;
        $assignment_question_points = 40;
        $total_points = 100;
        Submission::create(['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'score' => $submission_score,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => false,
            'submission' => 'some submission']);
        SubmissionFile::create(['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'score' => $file_submission_score,
            'type' => 'text',
            'original_filename' => '',
            'submission' => 'some.pdf',
            'date_submitted' => Carbon::now()]);
        Score::create(['user_id' => $this->student_user->id, 'score' => $assignment_score, 'assignment_id' => $this->assignment->id]);
        DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->update(['points' => $assignment_question_points]);

        $this->assignment->total_points = $total_points;
        $this->assignment->points_per_question = "question weight";
        $this->assignment->save();
        $this->assignment_info['points_per_question'] = "question weight";
        $this->assignment_info['total_points'] = $total_points / 2;
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJson(['type' => 'success']);
        $new_assignment_score = Score::where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->first()
            ->score;
        $new_submission_score = Submission::where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)->first()
            ->where('question_id', $this->question->id)->first()
            ->score;
        $new_file_submission_score = SubmissionFile::where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->first()
            ->score;
        $new_assignment_question_points = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->first()
            ->points;
        $this->assertEquals($assignment_score / 2, $new_assignment_score, 'Scales the assignment score');
        $this->assertEquals($submission_score / 2, $new_submission_score, 'Scales the submission score');
        $this->assertEquals($file_submission_score / 2, $new_file_submission_score, 'Scales the file submission score');
        $this->assertEquals($assignment_question_points/2, $new_assignment_question_points , 'Scales the assignment question points');
    }


    /** @test */
    public function cannot_use_question_weight_for_alpha_courses()
    {
        $this->assignment_info['points_per_question'] = 'question weight';
        $this->course->alpha = 1;
        $this->course->save();

        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJson(['message' => 'Alpha courses cannot determine question points by weight.']);

        $this->actingAs($this->user)
            ->postJson("/api/assignments", $this->assignment_info)
            ->assertJson(['message' => 'Alpha courses cannot determine question points by weight.']);

    }


    /** @test */
    public function switching_from_question_weight_to_number_of_points_will_remove_the_weights()
    {
        $this->assignment->points_per_question = 'question weight';
        $this->assignment->save();
        $this->question_2 = factory(Question::class)->create(['page_id' => 1214214123]);
        DB::table('assignment_question')
            ->where('id', $this->assignment_question_id)
            ->update(['weight' => 1]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10,
            'order' => 1,
            'weight' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        $this->assignment_info['points_per_question'] = 'number of points';

        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJson(['type' => 'success']);

        $num_with_null = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('weight', null)
            ->count();
        $this->assertEquals(2, $num_with_null);

    }


    /** @test */
    public function completed_must_have_a_valid_default_completion_scoring_mode()
    {
        $this->assignment_info['scoring_type'] = 'c';
        $this->assignment_info['default_completion_scoring_mode'] = "some letters";

        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJsonValidationErrors('default_completion_scoring_mode');

        $this->assignment_info['default_completion_scoring_mode'] = -100;

        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJsonValidationErrors('default_completion_scoring_mode');

        $this->assignment_info['default_completion_scoring_mode'] = 4000;

        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJsonValidationErrors('default_completion_scoring_mode');
    }


    /** @test */
    public function delayed_assignment_cannot_switch_to_clicker_if_there_are_no_technology_questions()
    {
        DB::table('assignment_question')
            ->where('id', $this->assignment_question_id)
            ->update(['open_ended_submission_type' => 0]);

        $this->question->technology_iframe = '';
        $this->question->save();

        $this->assignment_info['assessment_type'] = 'clicker';
        $new_assessment_type = ucfirst($this->assignment_info['assessment_type']);
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
        $this->assignment_info['number_of_allowed_attempts'] = '1';
        $this->assignment_info['solutions_availability'] = 'manual';
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}", $this->assignment_info)
            ->assertJson(['message' => "This assignment already has non-Learning Tree assessments in it.  If you would like to change the assessment type, please first remove those assessments."]);

    }
}
