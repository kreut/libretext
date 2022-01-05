<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Question;
use App\MyFavorite;
use App\Section;
use App\User;
use App\Traits\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SavedQuestionsTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $commons_user = factory(User::class)->create(['email'=> 'commons@libretexts.org']);
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->commons_course = factory(Course::class)->create(['user_id' => $commons_user->id]);
        $this->commons_assignment = factory(Assignment::class)->create(['course_id' => $this->commons_course->id]);
        $this->question = factory(Question::class)->create(['page_id' => 123131]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->commons_assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
    }

    /** @test */
    public function instructor_can_get_saved_questions_from_a_course_that_is_a_commons_course()
    {

        $this->actingAs($this->user)
            ->getJson("/api/saved-questions/{$this->commons_assignment->id}")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function non_instructor_cannot_get_saved_questions_from_a_course_that_is_a_commons_course()
    {

        $this->actingAs($this->student_user)
            ->getJson("/api/saved-questions/with-course-level-usage-info/{$this->commons_assignment->id}")
            ->assertJson(['message' => 'You are not allowed to retrieve saved questions for that assignment.']);
    }



    /** @test */
    public function non_instructors_cannot_destroy_saved_questions()
    {

        $this->actingAs($this->student_user)
            ->deleteJson("/api/saved-questions/{$this->question->id}")
            ->assertJson(['message' => 'You are not allowed to remove saved questions.']);
    }

    /** @test */
    public function instructors_can_destroy_saved_questions()
    {
        $assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
        $savedQuestion = new MyFavorite();
        $savedQuestion->question_id = $this->question->id;
        $savedQuestion->open_ended_submission_type = 0;
        $savedQuestion->user_id = $this->user->id;
        $savedQuestion->save();
        $this->assertCount(1, MyFavorite::all());
        $this->actingAs($this->user)
            ->deleteJson("/api/saved-questions/{$this->question->id}")
            ->assertJson(['message' => 'The question has been removed from your saved list.']);
        $this->assertCount(0, MyFavorite::all());
    }



    /** @test */
    public function can_save_questions_from_a_course_that_is_a_commons_course()
    {

        $this->actingAs($this->user)
            ->postJson("/api/saved-questions/{$this->commons_assignment->id}", ['question_ids' => [$this->question->id]])
            ->assertJson(['message' => 'Your newly saved questions can now be imported into one of your assignments.']);
    }


    /** @test */
    public function cannot_save_questions_from_a_course_that_is_not_a_commons_course()
    {

        $this->actingAs($this->user)
            ->postJson("/api/saved-questions/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to save questions from this course.']);
    }

    /** @test */
    public function cannot_save_questions_from_a_commons_course_if_not_an_instructor()
    {

        $this->actingAs($this->student_user)
            ->postJson("/api/saved-questions/{$this->commons_assignment->id}")
            ->assertJson(['message' => 'You are not allowed to save questions from this course.']);
    }

    /** @test */
    public function can_only_save_questions_from_a_commons_course()
    {


        $this->actingAs($this->user)
            ->postJson("/api/saved-questions/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to save questions from this course.']);
    }

    /** @test */

    public function non_instructor_cannot_get_saved_questions()
    {

        $this->actingAs($this->student_user)
            ->getJson("/api/saved-questions/with-course-level-usage-info/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to retrieve saved questions for that assignment.']);
    }

    /** @test */
    public function instructor_can_get_saved_questions()
    {

        $this->actingAs($this->user)
            ->getJson("/api/saved-questions/with-course-level-usage-info/{$this->assignment->id}")
            ->assertJson(['type' => 'success']);
    }


}
