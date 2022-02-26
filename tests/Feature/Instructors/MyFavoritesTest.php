<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Question;
use App\MyFavorite;
use App\SavedQuestionsFolder;
use App\User;
use App\Traits\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MyFavoritesTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $commons_user = factory(User::class)->create(['email' => 'commons@libretexts.org']);
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user_2->id, 'public' => 0]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course_2->id]);
        $this->commons_course = factory(Course::class)->create(['user_id' => $commons_user->id]);
        $this->commons_assignment = factory(Assignment::class)->create(['course_id' => $this->commons_course->id]);
        $this->question = factory(Question::class)->create(['page_id' => 123131]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 1231232331]);
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->commons_assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
    }

    /** @test */
    public function can_only_save_to_my_favorites_if_commons_or_public_or_course_owner_or_question_editor()
    {
        $this->question->public = 0;
        $this->question->save();
        $data = ['question_ids' => [$this->question->id],
            'folder_id' => $this->saved_questions_folder->id,
            'chosen_assignment_ids' => [0]];
        $this->actingAs($this->user)->postJson("/api/my-favorites", $data)
            ->assertJson(['message' => 'You are not allowed to save that question to your Favorites.']);
        $data['chosen_assignment_ids'] = [$this->assignment->id];

        $data = ['question_ids' => [$this->question->id],
            'folder_id' => $this->saved_questions_folder->id,
            'chosen_assignment_ids' => [$this->assignment->id]];

        //ok if course owner.
        $this->actingAs($this->user)->postJson("/api/my-favorites", $data)
            ->assertJson(['message' => 'The question has been added to your My Favorites Folder.']);

//ok if commons
        $data['chosen_assignment_ids'] = [$this->commons_assignment->id];
        $this->actingAs($this->user)->postJson("/api/my-favorites", $data)
            ->assertJson(['message' => 'The question has been added to your My Favorites Folder.']);

        //not ok if not your course and not public

        $data['chosen_assignment_ids'] = [$this->assignment_2->id];
        $this->actingAs($this->user)->postJson("/api/my-favorites", $data)
            ->assertJson(['message' => 'You are not allowed to save that question to your Favorites.']);

        //ok if not your course and not public
        $this->course_2->public = 1;
        $this->course_2->save();
        $data['chosen_assignment_ids'] = [$this->assignment_2->id];
        $this->actingAs($this->user)->postJson("/api/my-favorites", $data)
            ->assertJson(['message' => 'The question has been added to your My Favorites Folder.']);

        //ok if question editor
        $data['chosen_assignment_ids'] = [$this->assignment_2->id];
        $this->question->question_editor_user_id = $this->user->id;
        $this->question->save();
        $this->actingAs($this->user)->postJson("/api/my-favorites", $data)
            ->assertJson(['message' => 'The question has been added to your My Favorites Folder.']);

    }

    /** @test */
    public function instructor_cannot_get_my_favorite_questions_from_a_course_that_is_not_a_commons_course()
    {

        $this->actingAs($this->user)
            ->getJson("/api/my-favorites/commons/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to get the My Favorites questions for this assignment.']);
    }

    /** @test */
    public function instructor_can_get_my_favorite_questions_from_a_course_that_is_a_commons_course()
    {

        $this->actingAs($this->user)
            ->getJson("/api/my-favorites/commons/{$this->commons_assignment->id}")
            ->assertJson(['type' => 'success']);
    }


    /** @test */
    public function cannot_destroy_a_my_favorites_question_if_its_not_in_your_folder()
    {
        $this->saved_questions_folder->user_id = $this->user->id + 1;
        $this->saved_questions_folder->save();
        $this->actingAs($this->user)
            ->deleteJson("/api/my-favorites/folder/{$this->saved_questions_folder->id}/question/{$this->question->id}")
            ->assertJson(['message' => 'You are not allowed to remove that question from My Favorites.']);
    }

    /** @test */
    public function cannot_destroy_a_my_favorites_question_if_the_question_is_not_in_your_folder()
    {
        $myFavorite = new MyFavorite();
        $myFavorite->user_id = $this->user->id;
        $myFavorite->folder_id = $this->saved_questions_folder->id;
        $myFavorite->question_id = $this->question_2->id;
        $myFavorite->open_ended_submission_type = 0;
        $myFavorite->save();
        $this->actingAs($this->user)
            ->deleteJson("/api/my-favorites/folder/{$this->saved_questions_folder->id}/question/{$this->question->id}")
            ->assertJson(['message' => 'You are not allowed to remove that question from My Favorites.']);
    }

    /** @test */
    public function can_destroy_a_my_favorites_question_if_its_in_your_folder()
    {
        $myFavorite = new MyFavorite();
        $myFavorite->user_id = $this->user->id;
        $myFavorite->folder_id = $this->saved_questions_folder->id;
        $myFavorite->question_id = $this->question->id;
        $myFavorite->open_ended_submission_type = 0;
        $myFavorite->save();
        $this->actingAs($this->user)
            ->deleteJson("/api/my-favorites/folder/{$this->saved_questions_folder->id}/question/{$this->question->id}")
            ->assertJson(['message' => 'The question has been removed from your favorites.']);
        $this->assertDatabaseCount('my_favorites', 0);
    }

    /** @test * */
    public function must_own_the_favorites_folder_to_save_to_it()
    {
        $data = ['question_ids' => [$this->question->id],
            'folder_id' => 0,
            'chosen_assignment_ids' => [0]];

        $this->actingAs($this->user)->postJson("/api/my-favorites", $data)
            ->assertJson(['message' => 'You are not allowed to save that the question to that folder.']);
    }


}
