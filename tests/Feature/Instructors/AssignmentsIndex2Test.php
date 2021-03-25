<?php

namespace Tests\Feature\Instructors;

use App\AssignToUser;
use App\Course;
use App\Enrollment;
use App\FinalGrade;
use App\Grader;
use App\LearningTree;
use App\Question;
use App\Section;
use App\User;
use App\Assignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Traits\Test;

class AssignmentsIndex2Test extends TestCase
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


        $this->assignment_3 = factory(Assignment::class)->create(['course_id' => $this->course->id, 'order' => 2]);
        $this->question = factory(Question::class)->create(['page_id' => 1]);
        $this->original_assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);


        $this->leraning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id]);
        $this->original_assignment_question_learning_tree_id = DB::table('assignment_question_learning_tree')->insertGetId([
            'assignment_question_id' => $this->original_assignment_question_id,
            'learning_tree_id' => $this->leraning_tree->id
        ]);

        $this->course_3 = factory(Course::class)->create(['user_id' => $this->user->id]);


        $this->user_2 = factory(User::class)->create();
        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user_2->id]);
        $this->assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course_2->id]);

        $this->grader_user = factory(User::class)->create();
        $this->grader_user->role = 4;
        Grader::create(['user_id' => $this->grader_user->id, 'section_id' => $this->section->id]);
        $finalGrade = new FinalGrade();

        FinalGrade::create(['course_id' => $this->course->id,
            'letter_grades' => $finalGrade->defaultLetterGrades()]);


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
            'notifications' => 1,
            'assignment_group_id' => 1];

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

        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        Enrollment::create(['course_id' => $this->course->id,
            'section_id' => $this->section->id,
            'user_id' => $this->student_user->id]);

        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;
        $this->section_1 = $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        Enrollment::create(['course_id' => $this->course->id,
            'section_id' => $this->section_1->id,
            'user_id' => $this->student_user_2->id]);

    }

    /** @test */

    public function cannot_repeat_assign_tos()
    {
        $assignment_info = $this->assignment_info;
        $groups = [['value' => ['section_id' => $this->section->id], 'text' => $this->section->name],
            ['value' => ['section_id' => $this->section->id], 'text' => $this->section->name]];
        $assignment_info = $this->createAssignTosFromGroups($assignment_info, $groups);

        $this->actingAs($this->user)->postJson("/api/assignments", $assignment_info)
            ->assertJson(['message' => "{$this->section->name} was chosen twice as an assign to."]);

    }

    /** @test */
    public
    function the_correct_user_is_assigned_an_assignment_with_everybody()
    {
        $student_user_ids = [$this->student_user->id, $this->student_user_2->id];
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJson(['type' => 'success']);
        $this->assertEquals(count($student_user_ids), AssignToUser::whereIn('user_id', $student_user_ids)->get()->count());
    }

    /** @test */
    public
    function the_correct_user_is_assigned_an_assignment_with_sections()
    {

        $assignment_info = $this->assignment_info;
        $groups = [['value' => ['section_id' => $this->section->id], 'text' => $this->section->name]];
        $assignment_info = $this->createAssignTosFromGroups($assignment_info, $groups);


        $this->actingAs($this->user)->postJson("/api/assignments", $assignment_info)
            ->assertJson(['type' => 'success']);
        $this->assertEquals(1, AssignToUser::all()->count(), 'Only one of the two users has been assigned');

    }


    /** @test */
    public
    function the_correct_user_is_assigned_an_assignment_with_different_sections()
    {
        foreach ($this->student_user_ids as $student_user_id) {
            Enrollment::create(['course_id' => $this->course->id,
                'section_id' => $this->section->id,
                'user_id' => $student_user_id]);
        }
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJson(['type' => 'success']);
        $this->assertEquals(count($this->student_user_ids), AssignToUser::whereIn('user_id', $this->student_user_ids)->get()->count());
    }


    /** @test */

    public
    function non_owner_cannot_reorder_assignments()
    {
        $this->actingAs($this->user_2)->patchJson("/api/assignments/{$this->course->id}/order", [
            'ordered_assignments' => [$this->assignment_2->id, $this->assignment->id]
        ])->assertJson(['message' => 'You are not allowed to re-order the assignments in that course.']);
    }

    /** @test */

    public
    function owner_can_reorder_assignments()
    {
        //dd($this->assignment->order . ' ' . $this->assignment_2->order);
        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->course->id}/order", [
            'ordered_assignments' => [$this->assignment_3->id, $this->assignment->id]
        ]);
        $assignments = DB::table('assignments')->where('course_id', $this->course->id)
            ->get()
            ->sortBy('order')
            ->pluck('id')
            ->toArray();
        $this->assertEquals([$this->assignment_3->id, $this->assignment->id], $assignments);

    }

    /** @test */

    public
    function owner_of_assignment_can_import_properties_and_questions()
    {
        $this->actingAs($this->user)->postJson("/api/assignments/import/{$this->course_3->id}",
            ['course_assignment' => "{$this->course->name} --- {$this->assignment->name}",
                'level' => 'properties_and_questions']);
        $imported_assignment = $this->course_3->assignments->first();
        $imported_assignment_question = DB::table('assignment_question')->where('assignment_id', $imported_assignment->id)->first();
        $original_assignment_question = DB::table('assignment_question')->where('id', $this->original_assignment_question_id)->first();

        $this->assertEquals($original_assignment_question->question_id, $imported_assignment_question->question_id);


    }

    /** @test */

    public
    function owner_of_assignment_can_import_properties_and_learning_trees()
    {
        $this->actingAs($this->user)->postJson("/api/assignments/import/{$this->course_3->id}",
            ['course_assignment' => "{$this->course->name} --- {$this->assignment->name}",
                'level' => 'properties_and_questions']);
        $imported_assignment = $this->course_3->assignments->first();
        $imported_assignment_question_id = DB::table('assignment_question')
            ->where('assignment_id', $imported_assignment->id)
            ->first()
            ->id;


        $original_assignment_question_id = DB::table('assignment_question')
            ->where('id', $this->original_assignment_question_id)
            ->first()
            ->id;

        $imported_assignment_question_learning_tree_id = DB::table('assignment_question_learning_tree')
            ->where('assignment_question_id', $imported_assignment_question_id)
            ->first()
            ->learning_tree_id;

        $original_assignment_question_learning_tree_id = DB::table('assignment_question_learning_tree')
            ->where('assignment_question_id', $original_assignment_question_id)
            ->first()
            ->learning_tree_id;

        $this->assertEquals($original_assignment_question_learning_tree_id, $imported_assignment_question_learning_tree_id);
    }


    /** @test */

    public
    function owner_of_assignment_can_import_just_properties()
    {
        $this->actingAs($this->user)->postJson("/api/assignments/import/{$this->course->id}",
            ['course_assignment' => "{$this->course->name} --- {$this->assignment->name}",
                'level' => 'properties_and_not_questions'])
            ->assertJson(['message' => "<strong>First Assignment Import</strong> has been imported without its questions.</br></br>Don't forget to change the dates associated with this assignment."]);


    }

    /** @test */

    public
    function importing_must_include_a_valid_level()
    {
        $this->actingAs($this->user)->postJson("/api/assignments/import/{$this->course->id}",
            ['course_assignment' => "{$this->course->name} --- {$this->assignment->name}",
                'level' => 'some fake level'])
            ->assertJson(['message' => "You should either choose 'properties and questions' or 'properties and not questions'."]);
    }


    /** @test */

    public
    function non_owner_of_assignment_cannot_import_it_to_their_course()
    {

        $this->actingAs($this->user)->postJson("/api/assignments/import/{$this->course_2->id}", [
            'course_assignment' => 'bogus course --- bogus assignment'
        ])->assertJson(['message' => 'You are not allowed to import assignments to this course.']);
    }


    /** @test */
    public
    function non_owner_of_assignment_cannot_create_it_from_template()
    {

        $this->actingAs($this->user_2)->postJson("/api/assignments/{$this->assignment->id}/create-assignment-from-template")
            ->assertJson(['message' => 'You are not allowed to create an assignment from this template.']);
    }

    /** @test */
    public
    function owner_of_assignment_can_create_it_from_template()
    {

        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/create-assignment-from-template")
            ->assertJson(['message' => "<strong>{$this->assignment->name} copy</strong> is using the same template as <strong>{$this->assignment->name}</strong>. Don't forget to add questions and update the assignment's dates."]);
    }


    /** @test */
    public
    function must_include_a_valid_default_open_ended_submission_type()
    {

        $this->assignment_info['default_open_ended_submission_type'] = "7";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['default_open_ended_submission_type']);
    }


    /** @test */

    public
    function complete_incomplete_scoring_type_cannot_be_learning_trees_assessment_types()
    {
        $this->assignment_info['scoring_type'] = 'c';
        $this->assignment_info['assessment_type'] = 'learning tree';
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors('scoring_type');
    }


    /** @test */

    public
    function complete_incomplete_scoring_type_must_have_a_late_policy_of_not_accepted()
    {
        $this->assignment_info['scoring_type'] = 'c';
        $this->assignment_info['late_policy'] = 'deduction';
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors('scoring_type');
    }


    /** @test */

    public
    function min_time_needed_in_learning_tree_must_be_valid()
    {
        $this->assignment_info['assessment_type'] = 'learning tree';
        $this->assignment_info['percent_earned_for_exploring_learning_tree'] = 150;
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors('percent_earned_for_exploring_learning_tree');
    }

    /** @test */

    public
    function percent_earned_for_exploring_learning_tree_must_be_valid()
    {
        $this->assignment_info['assessment_type'] = 'learning tree';
        //$this->assignment_info['min_time_needed_in_learning_tree'] = 10;
        $this->assignment_info['percent_earned_for_exploring_learning_tree'] = 50;
        $this->assignment_info['submission_count_percent_decrease'] = 60;
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors('submission_count_percent_decrease');

    }

    /** @test */

    public
    function final_submission_deadline_must_be_valid()
    {


    }

    /** @test */

    public
    function for_real_time_assessments_students_must_be_shown_scores()
    {
//todo

    }

    /** @test */

    public
    function for_real_time_assessments_students_must_be_shown_solutions()
    {
//todo

    }

    /** @test */

    public
    function for_real_time_assessments_students_must_be_shown_question_points()
    {
//todo

    }


    /** @test */

    public
    function submission_count_percent_decrease_must_be_valid()
    {
//todo

    }


    /** @test */
    public
    function can_only_toggle_show_scores_for_a_delayed_assignment()
    {
//todo

    }

    /** @test */
    public
    function can_only_toggle_show_points_per_question_for_a_delayed_assignment()
    {
//todo

    }

    /** @test */

    public
    function a_valid_late_policy_is_submitted()
    {
//todo
    }

    /** @test */
    public
    function a_valid_late_percent_is_submitted()
    {

        //todo
    }

    /** @test */
    public
    function a_valid_late_deduction_application_period_is_submitted()
    {
        //test once as well as with a time
        //todo

    }


    /** @test */
    public
    function non_owner_cannot_toggle_show_points_per_question()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-points-per-question/1")
            ->assertJson(['message' => 'You are not allowed to show/hide the points per question.']);
    }

    /** @test */
    public
    function owner_can_toggle_show_points_per_question()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-points-per-question/1")
            ->assertJson(['message' => 'Your students <strong>cannot</strong> view the points per question.']);
    }

    public
    function can_update_an_assignment_if_you_are_the_owner()
    {
        $this->assignment_info['name'] = 'Some new name';
        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}",
            $this->assignment_info)
            ->assertJson(['type' => 'success']);
    }


    /** @test */
    public
    function can_create_an_assignment()
    {
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJson(['type' => 'success']);

    }

    /** @test */

    public
    function must_be_of_a_valid_source()
    {
        $this->assignment_info['source'] = "";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['source']);


    }

    /** @test */
    public
    function must_include_an_assignment_name()
    {
        $this->assignment_info['name'] = "";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['name']);

    }

    /** @test */
    public
    function must_include_valid_available_on_date()
    {

        $this->assignment_info['available_from_date_0'] = "not a date";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['available_from_date_0']);

    }


    /** @test */
    public
    function must_include_valid_default_points_per_question()
    {

        $this->assignment_info['default_points_per_question'] = "";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);

        $this->assignment_info['default_points_per_question'] = "1.9";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);

        $this->assignment_info['default_points_per_question'] = "10000";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);

        $this->assignment_info['default_points_per_question'] = "-3";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);
    }


    /** @test */
    public
    function must_include_valid_due_date()
    {
        $this->assignment_info['due_date_0'] = "not a date";
        $this->assignment_info['due_0'] = "not a date";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['due_0']);
    }


    /** @test */
    public
    function must_include_valid_due_time()
    {
        $this->assignment_info['due_time_0'] = "not a time";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['due_time_0']);
    }

    /** @test */
    public
    function due_date_must_be_after_available_date()
    {
        $this->assignment_info['due_0'] = "1982-06-06";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['due_0']);
    }

    /** @test */
    public
    function must_include_valid_available_from_time()
    {

        $this->assignment_info['available_from_time_0'] = "not a time";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['available_from_time_0']);
    }


}
