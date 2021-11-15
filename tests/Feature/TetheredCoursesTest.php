<?php

namespace Tests\Feature;

use App\Assignment;
use App\BetaAssignment;
use App\BetaCourse;
use App\BetaCourseApproval;
use App\Course;
use App\Enrollment;
use App\LearningTree;
use App\Question;
use App\Section;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TetheredCoursesTest extends TestCase
{
    private $course;
    private $user;
    /**
     * @var Collection|Model|mixed
     */
    private $course_2;
    /**
     * @var Collection|Model|mixed
     */
    private $beta_course;
    /**
     * @var Collection|Model|mixed
     */
    private $assignment;
    /**
     * @var Collection|Model|mixed
     */
    private $beta_assignment;
    /**
     * @var Collection|Model|mixed
     */
    private $question;
    /**
     * @var Collection|Model|mixed
     */
    private $beta_user;
    private $user_2;
    private $assignment_remixer;
    private $learning_tree;

    /**
     * @var Collection|Model|mixed
     */


    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->beta_user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id, 'alpha' => 1]);
        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user_2->id]);


        $this->beta_course = factory(Course::class)->create(['user_id' => $this->beta_user->id]);

        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->beta_assignment = factory(Assignment::class)->create(['course_id' => $this->beta_course->id]);
        $this->learning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id]);
        factory(Question::class)->create(['library'=> $this->learning_tree->root_node_library, 'page_id' => $this->learning_tree->root_node_page_id]);

        BetaCourse::create(['id' => $this->beta_course->id, 'alpha_course_id' => $this->course->id]);
        BetaAssignment::create(['id' => $this->beta_assignment->id, 'alpha_assignment_id' => $this->assignment->id]);
        $this->question = factory(Question::class)->create(['page_id' => 3123123]);


        $this->assignment_remixer = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_remixer->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => '0'
        ]);

    }

    /** @test */
    public function correctly_gets_beta_assignment_for_instructors()
    {
        $this->actingAs($this->beta_user)->getJson("/api/beta-assignments/get-from-alpha-assignment/{$this->assignment->id}")
            ->assertJson(['beta_assignment_id' => $this->beta_assignment->id]);
    }

    /** @test */
    public function correctly_gets_beta_assignment_for_students()
    {
        $beta_student_user = factory(User::class)->create();
        $beta_student_user->role = 3;
        $beta_section = factory(Section::class)->create(['course_id' => $this->beta_course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $beta_student_user->id,
            'course_id' => $this->beta_course->id,
            'section_id' => $beta_section->id
        ]);
        $this->actingAs($beta_student_user)->getJson("/api/beta-assignments/get-from-alpha-assignment/{$this->assignment->id}")
            ->assertJson(['beta_assignment_id' => $this->beta_assignment->id]);
    }


    /** @test */
    public function cannot_remove_alpha_assignment_if_it_has_beta_assignment()
    {
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}")
            ->assertJson(['message' => 'You cannot delete an Alpha assignment with tethered Beta assignments.']);
    }

    /** @test */
    public function when_you_approve_adding_a_learning_tree_assessment_it_gets_stored_in_your_course()
    {

        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/learning-trees/{$this->learning_tree->id}")
            ->assertJson([
                'message' => 'The Learning Tree has been added to the assignment.'
            ]);
        $question = Question::where('page_id', $this->learning_tree->root_node_page_id)
            ->where('library', $this->learning_tree->root_node_library)
            ->first();

        $this->assertDatabaseHas('beta_course_approvals', [
            'beta_assignment_id' => $this->beta_assignment->id,
            'beta_question_id' => $question->id,
            'beta_learning_tree_id' => $this->learning_tree->id,
            'action' => 'add']);
    }


    /** @test */
    public function when_you_approve_removing_a_learning_tree_assessment_it_gets_stored_in_your_course()
    {


        /* $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/learning-trees/{$this->learning_tree->id}")
                ->assertJson([
                    'message' => 'The Learning Tree has been added to the assignment.'
                ]);*/

        $Question = new Question();
        $question_id = $Question->getQuestionIdsByPageId($this->learning_tree->root_node_page_id, $this->learning_tree->root_node_library, false)[0];

        $assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question_id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);

        $beta_assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->beta_assignment->id,
            'question_id' => $question_id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);

        DB::table('assignment_question_learning_tree')->insert([
            'assignment_question_id' => $assignment_question_id,
            'learning_tree_id' => $this->learning_tree->id
        ]);

        DB::table('assignment_question_learning_tree')->insert([
            'assignment_question_id' => $beta_assignment_question_id,
            'learning_tree_id' => $this->learning_tree->id
        ]);

        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/$question_id")
            ->assertJson(['type' => 'info']);


        $this->assertDatabaseHas('beta_course_approvals', [
            'beta_assignment_id' => $this->beta_assignment->id,
            'beta_question_id' => $question_id,
            'beta_learning_tree_id' => $this->learning_tree->id,
            'action' => 'remove']);
    }


    /** @test */
    public function beta_course_approval_is_added_when_you_remove_an_assessment_from_an_alpha_course()
    {
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);

        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'info']);
        $this->assertDatabaseHas('beta_course_approvals', [
            'beta_assignment_id' => $this->beta_assignment->id,
            'beta_question_id' => $this->question->id,
            'action' => 'remove']);

    }


    public function nonowner_cannot_untether_beta_course()
    {
        {
            $this->actingAs($this->user_2)->deleteJson("/api/beta-courses/untether/{$this->beta_course->id}",
                ['course_id' => $this->beta_course->id,
                    'name' => $this->beta_course->name]
            )->assertJson(['message' => "You are not allowed to untether this Beta Course."]);


        }

    }

    /** @test */
    public function owner_cannot_untether_beta_course_without_correct_name_confirmation()
    {
        $this->actingAs($this->beta_user)->deleteJson("/api/beta-courses/untether/{$this->beta_course->id}",
            ['course_id' => $this->beta_course->id,
                'name' => 'bad name']
        )->assertJsonValidationErrors('name');


    }

    /** @test */
    public function owner_can_untether_beta_course()
    {
        $this->actingAs($this->beta_user)->deleteJson("/api/beta-courses/untether/{$this->beta_course->id}",
            ['course_id' => $this->beta_course->id,
                'name' => $this->beta_course->name]
        )->assertJson(['message' => "This course has been untethered from {$this->course->name}."]);


    }

    /** @test */
    public function course_cannot_be_both_alpha_and_beta()
    {

        $this->actingAs($this->beta_user)->patchJson("/api/courses/{$this->beta_course->id}", [
            'alpha' => '1',
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10',
            'term' => 'some term',
            'crn' => 'some crn'
        ])->assertJson(['message' => "You can't change a Beta course into an Alpha course."]);

    }

    /** @test */
    public function cannot_change_alpha_course_to_non_alpha_course_if_at_least_one_beta_course()
    {

        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}", [
            'alpha' => '0',
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10',
            'term' => 'some term',
            'crn' => 'some crn'
        ])->assertJson(['message' => "You are trying to change an Alpha course into a non-Alpha course but Beta courses are currently tethered to this course."]);

    }


    /** @test */
    public function when_you_approve_adding_an_assessment_it_gets_stored_in_your_course()
    {

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => '0'
        ]);

        DB::table('beta_course_approvals')->insert(['beta_assignment_id' => $this->beta_assignment->id,
            'beta_question_id' => $this->question->id,
            'beta_learning_tree_id' => 0,
            'action' => 'add']);

        $this->actingAs($this->beta_user)->postJson("/api/assignments/{$this->beta_assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('assignment_question', [
            'assignment_id' => $this->beta_assignment->id,
            'question_id' => $this->question->id]);
        $this->assertDatabaseCount('beta_course_approvals', 0);

    }

    /** @test */
    public function when_you_approve_removing_an_assessment_it_gets_removed_from_your_course()
    {
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->beta_assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);

        DB::table('beta_course_approvals')->insert(['beta_assignment_id' => $this->beta_assignment->id,
            'beta_question_id' => $this->question->id,
            'beta_learning_tree_id' => 0,
            'action' => 'remove']);

        $this->actingAs($this->beta_user)->deleteJson("/api/assignments/{$this->beta_assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'info']);

        $this->assertDatabaseCount('beta_course_approvals', 0);

    }

    /** @test */
    public function beta_course_approval_is_added_when_you_add_an_assessment_to_an_alpha_course()
    {

        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('beta_course_approvals', [
            'beta_assignment_id' => $this->beta_assignment->id,
            'beta_question_id' => $this->question->id,
            'action' => 'add']);
    }

    /** @test */
    public function beta_course_approval_is_added_when_you_add_an_assessment_via_the_remixer_to_an_alpha_course()
    {
        $data['chosen_questions'] = [
            ['question_id' => $this->question->id,
                'assignment_id' => $this->assignment_remixer->id]
        ];
        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}/remix-assignment-with-chosen-questions",
            $data);

        $this->assertDatabaseHas('beta_course_approvals', [
            'beta_assignment_id' => $this->beta_assignment->id,
            'beta_question_id' => $this->question->id,
            'action' => 'add']);

    }

    /** @test */
    public function only_owner_can_get_list_of_beta_courses()
    {
        $this->actingAs($this->user)->getJson("api/beta-courses/get-from-alpha-course/{$this->course_2->id}")
            ->assertJson(['message' => 'You are not allowed to retrieve those Beta courses.']);

    }

    /** @test */
    public function correctly_gets_the_list_of_beta_courses()
    {
        $response = $this->actingAs($this->user)->getJson("api/beta-courses/get-from-alpha-course/{$this->course->id}");
        $this->assertEquals($response->original['beta_courses'][0]->name, $this->beta_course->name);

    }

    /** @test */
    public function cannot_remove_alpha_course_if_it_has_beta_courses()
    {
        $this->actingAs($this->user)->deleteJson("/api/courses/{$this->course->id}")
            ->assertJson(['message' => 'You cannot delete an Alpha course with tethered Beta courses.']);
    }


    /** @test */
    public function must_be_an_alpha_course_to_import_as_a_beta_course()
    {
        $this->course->alpha = 0;
        $this->course->save();
        $this->actingAs($this->user)->postJson("/api/courses/import/{$this->course->id}", [
            'import_as_beta' => 1
        ])->assertJson(['message' => 'You cannot import this course as a Beta course since the original course is not an Alpha course.']);
    }



    /** @test */
    public function creates_a_beta_course_when_importing_an_alpha_course()
    {
        $current_num = count(BetaCourse::all());
        $this->course_2->alpha = 1;
        $this->course_2->save();
        $this->actingAs($this->user)->postJson("/api/courses/import/{$this->course_2->id}", [
            'import_as_beta' => 1
        ]);
        $this->assertEquals($current_num + 1, count(BetaCourse::all()));
    }


    public function correctly_forwards_alpha_course_user_to_beta_assignment_question()
    {


    }


    /** TODO */
    public function beta_course_approval_is_added_when_you_add_an_assessment_via_direct_import_to_an_alpha_course()
    {
        $this->actingAs($this->user)->postJson("/api/questions/{$this->assignment->id}/direct-import-questions",
            ['direct_import' => "query-1860,query-1862"])
            ->assertJson(['page_ids_added_to_assignment' => 'query-1860, query-1862']);
        $this->assertDatabaseHas('beta_course_approvals', [
            'beta_assignment_id' => $this->beta_assignment->id,
            'beta_question_id' => $this->question->id,
            'action' => 'add']);
    }

}
