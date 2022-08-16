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

class AdaptMigratorTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['email' => 'commons@libretexts.org']);
        $this->is_me = factory(User::class)->create(['email' => 'me@me.com']);//Admin

        $this->default_non_instructor_editor = factory(User::class)->create(['email' => 'Default Non-Instructor Editor has no email']);
        $this->question_1 = factory(Question::class)->create(['library' => 'chem', 'page_id' => 355295]);
        $this->question_2 = factory(Question::class)->create(['library' => 'chem', 'page_id' => 355296]);
        $this->question_3 = factory(Question::class)->create(['library' => 'query', 'page_id' => 3512312]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        foreach ([$this->question_1, $this->question_2, $this->question_3] as $key => $question) {
            DB::table('assignment_question')->insert([
                'assignment_id' => $this->assignment->id,
                'question_id' => $question->id,
                'points' => 10,
                'order' => $key + 1,
                'open_ended_submission_type' => 'none'
            ]);
        }

    }

    /** @test */
    public
    function cannot_migrate_a_copy_of_a_question()
    {
        $this->question_1->copy_source_id = 1;
        $this->question_1->save();
        $this->actingAs($this->is_me)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/libretexts/migrate', ['question_id' => $this->question_1->id, 'assignment_id' => $this->assignment->id])
            ->assertJson(['question_message' => 'You cannot migrate a copy of a question.']);


    }


    /** @test */
    public
    function questions_in_learning_trees_must_have_input_html()
    {
        $learning_tree_json = <<<EOT
{"html":"","blockarr":[{"childwidth":806,"parent":-1,"id":0,"x":1223,"y":302,"width":318,"height":109},{"childwidth":0,"parent":0,"id":1,"x":941,"y":465.6166687011719,"width":242,"height":117},{"childwidth":0,"parent":0,"id":2,"x":1223,"y":465,"width":242,"height":117},{"childwidth":0,"parent":0,"id":3,"x":1505,"y":465,"width":242,"height":117}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"1"},{"name":"blockid","value":"0"},{"name":"page_id","value":"355294"},{"name":"library","value":"chem"}],"attr":[{"class":"blockelem noselect block"},{"style":"left: 761px; top: 244px; border: 2px solid; color: rgb(0, 191, 255);"}]},{"id":1,"parent":0,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"355295"},{"name":"library","value":"chem"},{"name":"blockid","value":"1"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 191, 255); left: 479px; top: 407.617px"}]},{"id":2,"parent":0,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"355296"},{"name":"library","value":"chem"},{"name":"blockid","value":"2"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 191, 255); left: 761px; top: 406.5px"}]},{"id":3,"parent":0,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"355297"},{"name":"library","value":"chem"},{"name":"blockid","value":"3"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 191, 255); left: 1043px; top: 406.5px"}]}]}
EOT;

        factory(LearningTree::class)->create(['user_id' => $this->user->id, 'learning_tree' => $learning_tree_json]);

        $this->actingAs($this->is_me)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/libretexts/migrate', ['assignment_id' => $this->assignment->id, 'question_id' => $this->question_1->id])
            ->assertJson(['question_message' => "Learning Tree issue; error logged with Eric"]);

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
    function admin_can_migrate_a_single_question()
    {
        $this->actingAs($this->is_me)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->post('/api/libretexts/migrate', ['question_id' => $this->question_1->id, 'assignment_id' => $this->assignment->id])
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('questions', [
            'id' => $this->question_1->id,
            'library' => 'adapt',
            'page_id' => $this->question_1->id]);

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
