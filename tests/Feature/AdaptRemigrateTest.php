<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\LearningTree;
use App\Question;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function env;
use function factory;

class AdaptRemigrateTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['email' => 'commons@libretexts.org']);
        $this->is_me = factory(User::class)->create(['email' => 'me@me.com']);//Admin

        $this->default_non_instructor_editor = factory(User::class)->create(['email' => 'Default Non-Instructor Editor has no email']);
        $this->question_1 = factory(Question::class)->create(['library' => 'adapt', 'page_id' => 355295]);
        $this->question_2 = factory(Question::class)->create(['library' => 'adapt', 'page_id' => 355296]);
        //$this->question_3 = factory(Question::class)->create(['library' => 'adapt', 'page_id' => 3512312]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        foreach ([$this->question_1, $this->question_2] as $key => $question) {
            DB::table('assignment_question')->insert([
                'assignment_id' => $this->assignment->id,
                'question_id' => $question->id,
                'points' => 10,
                'order' => $key + 1,
                'open_ended_submission_type' => 'none'
            ]);
            DB::table('adapt_migrations')
                ->insert(['original_library' => 'chem',
                    'assignment_id' => $this->assignment->id,
                    'original_page_id' => $question->page_id,
                    'new_page_id' => $question->id,
                    'created_at' => now(),
                    'updated_at' => now()]);
        }


    }


    /** @test */
    public
    function admin_can_remigrate_a_single_question()
    {
        $this->actingAs($this->is_me)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/libretexts/migrate', ['question_id' => $this->question_1->id, 'assignment_id' => $this->assignment->id])
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('questions', [
            'id' => $this->question_1->id,
            'library' => 'adapt']);

    }

    /** @test */
    public
    function cannot_remigrate_a_copy_of_a_question()
    {
        $this->question_1->copy_source_id = 1;
        $this->question_1->save();
        $this->actingAs($this->is_me)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/libretexts/migrate', ['question_id' => $this->question_1->id, 'assignment_id' => $this->assignment->id])
            ->assertJson(['question_message' => 'You cannot migrate a copy of a question. (note to Delmar: not sure if this really true!  Shoot me an email)']);


    }


    /** @test */
    public
    function either_an_assignment_or_question_must_be_chosen()
    {
        $this->actingAs($this->is_me)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/libretexts/migrate')
            ->assertJson(['message' => "Need an assignment and question to migrate."]);

    }

    /** @test */
    public function course_must_be_a_commons_course()
    {
        $this->user->email = "not_commons@libretexts.org";
        $this->user->save();
        $this->actingAs($this->is_me)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/libretexts/migrate', ['assignment_id' => $this->assignment->id, 'question_id' => $this->question_1->id])
            ->assertJson(['message' => "This question doesn't come from a Commons course."]);

    }

    /** @test */
    public function question_moved_to_folder_in_default_non_instructor_editor_account()
    {
        $this->actingAs($this->is_me)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/libretexts/migrate', ['question_id' => $this->question_1->id, 'assignment_id' => $this->assignment->id])
            ->assertJson(['type' => 'success']);
        $saved_question_folder = DB::table('saved_questions_folders')->where('user_id', $this->default_non_instructor_editor->id)->first();
        $this->assertEquals("{$this->course->name} --- {$this->assignment->name}", $saved_question_folder->name);
        $this->assertDatabaseHas('questions', ['id' => $this->question_1->id, 'folder_id' => $saved_question_folder->id]);
    }

    /** @test */
    public
    function question_must_be_one_that_was_remigrated()
    {
        DB::table('adapt_migrations')->delete();
        $this->actingAs($this->is_me)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/libretexts/migrate', ['question_id' => $this->question_1->id, 'assignment_id' => $this->assignment->id])
            ->assertJson(['question_message' => 'Could not find this question to be re-migrated.']);

    }



    /** @test */
    public
    function non_admin_cannot_migrate()
    {
        $this->actingAs($this->user)
            ->post('/api/libretexts/migrate')
            ->assertJson(['message' => "You are not allowed to migrate questions from the libraries to ADAPT."]);
    }



}
