<?php

namespace Tests\Feature\Instructors;

use App\Question;
use App\User;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CopyQuestionTest extends TestCase
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
    public function admin_can_copy_questions()
    {

        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/questions/copy', [
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->user->id
                ]
            )->assertJson(['message' => "{$this->question->title} has been copied and {$this->user->first_name} {$this->user->last_name} has been given editing rights."]);
    }

    /** @test */
    public function non_adapt_question_cannot_be_copied()
    {
        $this->question->library = 'chem';
        $this->question->save();


        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/questions/copy', [
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->user->id
                ]
            )->assertJson(['message' => "You cannot copy this question since it is not a native ADAPT question."]);
    }

    /** @test */
    public function question_editor_must_be_instructor_or_non_instructor_editor()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/questions/copy', [
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
            ->post('/api/questions/copy', [
                    'question_id' => 0,
                    'question_editor_user_id' => $this->user->id
                ]
            )->assertJson(['message' => "Question 0 does not exist."]);
    }




    /** @test */
    public function non_admin_cannot_copy_questions_to_another_account()
    {
        $this->actingAs($this->user)
            ->post('/api/questions/copy', [
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->admin_user->id
                ]
            )->assertJson(['message' => "You cannot copy a question to someone else's account."]);
    }

    /** @test */
    public function non_instructor_cannot_copy_questions()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->user)
            ->post('/api/questions/copy', [
                    'question_id' => $this->question->id,
                    'question_editor_user_id' => $this->admin_user->id
                ]
            )->assertJson(['message' => "You are not allowed to copy questions."]);
    }


}
