<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Question;
use App\SavedQuestionsFolder;
use App\User;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloneQuestionTest extends TestCase
{
    use DatabaseTransactions;

    public function setup(): void
    {

        parent::setUp();
        $this->question = factory(Question::class)->create(['library' => 'adapt', 'page_id' => 2989818, 'title' => 'Some title']);
        $this->admin_user = factory(User::class)->create();
        $this->user = factory(User::class)->create();

    }

    /** @test */
    public function will_create_a_cloned_questions_folder_if_none_exist()
    {
        $user_1 = factory(User::class)->create(['role' => 2]);
        $this->actingAs($user_1)
            ->getJson('/api/saved-questions-folders/cloned-questions-folder')
            ->assertJson(['type' => "success"]);
        $this->assertDatabaseHas('saved_questions_folders',['name'=> 'Cloned Questions','user_id'=> $user_1->id]);

    }


    /** @test */
    public function non_instructor_or_editor_cannot_get_cloned_questions_folder()
    {

        $user_1 = factory(User::class)->create(['role' => 3]);
        $this->actingAs($user_1)
            ->getJson('/api/saved-questions-folders/cloned-questions-folder')
            ->assertJson(['message' => "You are not allowed to retrieve the Cloned Questions folder."]);
    }

    /** @test */
    public function private_question_you_do_not_own_cannot_be_cloned()
    {

        $user_1 = factory(User::class)->create(['role' => 2]);
        $my_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $user_1->id, 'type' => 'my_questions']);
        $course = factory(Course::class)->create(['user_id' => $user_1->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);
        $this->question->public = 0;
        $this->question->save();

        $this->actingAs($user_1)
            ->post('/api/questions/clone', [
                    'clone_to_folder_id' => $my_questions_folder->id,
                    'assignment_id' => $assignment->id,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $user_1->id
                ]
            )->assertJson(['message' => "This is a private question and cannot be cloned."]);
    }

    /** @test */
    public function question_with_a_restrictive_license_you_do_not_own_cannot_be_cloned()
    {

        $user_1 = factory(User::class)->create(['role' => 2]);
        $my_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $user_1->id, 'type' => 'my_questions']);
        $course = factory(Course::class)->create(['user_id' => $user_1->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);
        $this->question->license = 'arr';
        $this->question->save();

        $this->actingAs($user_1)
            ->post('/api/questions/clone', [
                    'clone_to_folder_id' => $my_questions_folder->id,
                    'assignment_id' => $assignment->id,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $user_1->id
                ]
            )->assertJson(['message' => "Due to licensing restrictions, this question cannot be cloned."]);

    }


    /** @test */
    public function make_sure_admin_acting_as_really_is_admin()
    {
        $this->actingAs($this->user)
            ->post('/api/questions/clone', [
                    'acting_as' => 'admin',
                    'assignment_id' => 0,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->user->id
                ]
            )->assertJson(['message' => "You are not Admin."]);
    }

    /** @test */
    public function admin_can_copy_questions()
    {

        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/questions/clone', [
                    'acting_as' => 'admin',
                    'assignment_id' => 0,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->user->id
                ]
            )->assertJson(['message' => "{$this->question->title} has been cloned and {$this->user->first_name} {$this->user->last_name} has been given editing rights."]);
    }


    /** @test */
    public function question_editor_must_be_instructor_or_non_instructor_editor()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/questions/clone', [
                    'acting_as' => 'admin',
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->user->id
                ]
            )->assertJson(['message' => "The new owner must be an instructor or non-instructor editor."]);

    }

    /** @test */
    public function question_id_must_be_valid()
    {
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/questions/clone', [
                    'question_id' => 0,
                    'question_editor_user_id' => $this->user->id
                ]
            )->assertJson(['message' => "Question 0 does not exist."]);
    }


    /** @test */
    public function non_admin_cannot_copy_questions_to_another_account()
    {
        $this->actingAs($this->user)
            ->post('/api/questions/clone', [
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->admin_user->id
                ]
            )->assertJson(['message' => "You cannot clone a question to someone else's account."]);
    }

    /** @test */
    public function assignment_id_must_be_valid()
    {
        $this->actingAs($this->user)
            ->post('/api/questions/clone', [
                    'assignment_id' => -1,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->admin_user->id
                ]
            )->assertJson(['message' => "You cannot clone a question to someone else's account."]);
    }

    /** @test */
    public function must_clone_to_an_assignment_you_own()
    {
        $user_1 = factory(User::class)->create(['role' => 2]);
        $course = factory(Course::class)->create(['user_id' => $user_1->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);

        $this->actingAs($this->user)
            ->post('/api/questions/clone', [
                    'assignment_id' => $assignment->id,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->user->id
                ]
            )->assertJson(['message' => "You cannot clone the question to an assignment that you don't own."]);

    }

    /** @test */
    public function you_must_choose_a_folder()
    {
        $user_1 = factory(User::class)->create(['role' => 2]);
        $course = factory(Course::class)->create(['user_id' => $user_1->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);

        $this->actingAs($user_1)
            ->post('/api/questions/clone', [
                    'assignment_id' => $assignment->id,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $user_1->id
                ]
            )->assertJson(['message' => "Please choose a folder."]);

    }

    /** @test */
    public function folder_must_exist()
    {
        $user_1 = factory(User::class)->create(['role' => 2]);
        $course = factory(Course::class)->create(['user_id' => $user_1->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);

        $this->actingAs($user_1)
            ->post('/api/questions/clone', [
                    'clone_to_folder_id' => -1,
                    'assignment_id' => $assignment->id,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $user_1->id
                ]
            )->assertJson(['message' => "That folder does not exist."]);
    }


    /** @test */
    public function you_must_own_the_folder()
    {

        $my_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);
        $user_1 = factory(User::class)->create(['role' => 2]);
        $course = factory(Course::class)->create(['user_id' => $user_1->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);

        $this->actingAs($user_1)
            ->post('/api/questions/clone', [
                    'clone_to_folder_id' => $my_questions_folder->id,
                    'assignment_id' => $assignment->id,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $user_1->id
                ]
            )->assertJson(['message' => "You do not own that folder."]);

    }

    /** @test */
    public function question_gets_added_to_the_assignment()
    {

        $user_1 = factory(User::class)->create(['role' => 2]);
        $my_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $user_1->id, 'type' => 'my_questions']);
        $course = factory(Course::class)->create(['user_id' => $user_1->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);

        $this->actingAs($user_1)
            ->post('/api/questions/clone', [
                    'clone_to_folder_id' => $my_questions_folder->id,
                    'assignment_id' => $assignment->id,
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $user_1->id
                ]
            )->assertJson(['message' => "The question has been cloned to your '$my_questions_folder->name' folder.<br><br>In addition, it has been added to $assignment->name."]);
        $num_assignment_questions = DB::table('assignment_question')->where('assignment_id', $assignment->id)->count();
        $this->assertEquals(1, $num_assignment_questions);

    }

    /** @test */
    public function non_instructor_cannot_copy_questions()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->user)
            ->post('/api/questions/clone', [
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->admin_user->id
                ]
            )->assertJson(['message' => "You are not allowed to clone questions."]);
    }


}
