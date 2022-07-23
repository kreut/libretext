<?php

namespace Tests\Feature\Admin;

use App\Assignment;
use App\Course;
use App\Question;
use App\Tag;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetaTagTest extends TestCase
{

    /**
     * @var array
     */
    private $meta_tags_info;

    public function setup(): void
    {

        parent::setUp();
        $this->admin_user = factory(User::class)->create(['email' => 'me@me.com']);//Admin
        $this->user = factory(User::class)->create();//not Admin
        $this->types = ['instructor', 'non-instructor editor'];
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        $this->question = factory(Question::class)->create(['author' => 'Mike Jones', 'page_id' => 234234]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        $this->question_2 = factory(Question::class)->create(['author' => 'Sam Sampson', 'page_id' => 2342345]);
        $this->meta_tags_info = [
            'tag_to_remove' => null,
            'tags_to_add' => '',
            'author' => '',
            'license' => null,
            'license_version' => null,
            'apply_to' => $this->question->id
        ];

    }

    /** @test */
    public function choosing_all_questions_changes_for_all_questions_in_the_assignment()
    {
        $this->meta_tags_info['apply_to'] = 'all';
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        $assignment_questions = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->get();
        $this->meta_tags_info['author'] = "Steven Smith";
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->patch("/api/meta-tags/course/{$this->course->id}/assignment/{$this->assignment->id}", $this->meta_tags_info)
            ->assertJson(['type' => 'success']);
        $questions = DB::table('questions')->where('author', $this->meta_tags_info['author'])->count();
        $this->assertEquals(count($assignment_questions), $questions);
    }

    /** @test */
    public function choosing_single_question_changes_for_one_question_in_the_assignment()
    {
        $this->meta_tags_info['apply_to'] = $this->question->id;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        $this->meta_tags_info['author'] = "Steven Smith";
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->patch("/api/meta-tags/course/{$this->course->id}/assignment/{$this->assignment->id}", $this->meta_tags_info)
            ->assertJson(['type' => 'success']);
        $questions = DB::table('questions')->where('author', $this->meta_tags_info['author'])->count();
        $this->assertEquals(1, $questions);
    }

    /** @test */
    public function admin_can_remove_tags()
    {
        $tag = factory(Tag::class)->create(['tag' => 'some tag']);
        DB::table('question_tag')->insert(['question_id' => $this->question->id, 'tag_id' => $tag->id]);
        $question_tags = DB::table('question_tag')->where('tag_id', $tag->id)->get();
        $this->assertCount(1, $question_tags, 'tag initially exists');
        $this->meta_tags_info['tag_to_remove'] = $tag->id;
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->patch("/api/meta-tags/course/{$this->course->id}/assignment/{$this->assignment->id}", $this->meta_tags_info)
            ->assertJson(['type' => 'success']);
        $question_tags = DB::table('question_tag')->where('tag_id', $tag->id)->get();
        $this->assertCount(0, $question_tags, 'tag no longer exists');

    }


    /** @test */
    public function admin_can_add_tags()
    {
        $this->meta_tags_info['tags_to_add'] = "tag 1, tag 2";
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->patch("/api/meta-tags/course/{$this->course->id}/assignment/{$this->assignment->id}", $this->meta_tags_info)
            ->assertJson(['type' => 'success']);
        $tag_1 = Tag::where('tag', 'tag 1')->first()->id;
        $tag_2 = Tag::where('tag', 'tag 2')->first()->id;
        $question_tags = DB::table('question_tag')->whereIn('tag_id', [$tag_1, $tag_2])->get();
        $this->assertCount(2, $question_tags);
    }

    /** @test */
    public function admin_can_update_license()
    {

        $this->meta_tags_info['license'] = "new license";
        $this->meta_tags_info['license_version'] = "new license version";
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->patch("/api/meta-tags/course/{$this->course->id}/assignment/{$this->assignment->id}", $this->meta_tags_info)
            ->assertJson(['type' => 'success']);
        $question = Question::find($this->question->id);
        $this->assertEquals($this->meta_tags_info['license'], $question->license, 'license updated');
        $this->assertEquals($this->meta_tags_info['license_version'], $question->license_version, 'license version updated');

    }


    /** @test */
    public function admin_can_update_author()
    {
        $this->meta_tags_info['author'] = "Steven Smith";
        $original_question_2_author = Question::find($this->question_2->id)->author;
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->patch("/api/meta-tags/course/{$this->course->id}/assignment/{$this->assignment->id}", $this->meta_tags_info)
            ->assertJson(['type' => 'success']);
        $question_author = Question::find($this->question->id)->author;
        $this->assertEquals($this->meta_tags_info['author'], $question_author, 'author name changed');

        $question_2_author = Question::find($this->question_2->id)->author;
        $this->assertEquals($original_question_2_author, $question_2_author, 'author name not changed');

    }

    /** @test */
    public function question_editor_user_id_switches_if_author_exists()
    {
        $this->meta_tags_info['author'] = "{$this->user->first_name} {$this->user->last_name}";

        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->patch("/api/meta-tags/course/{$this->course->id}/assignment/{$this->assignment->id}", $this->meta_tags_info)
            ->assertJson(['type' => 'success']);

        $question = Question::find($this->question->id);
        $this->assertEquals($this->user->id, $question->question_editor_user_id);
        $saved_questions_folder = DB::table('saved_questions_folders')
            ->where('user_id', $this->user->id)
            ->where('type', 'my_questions')
            ->first();
        $this->assertEquals($saved_questions_folder->id, $question->folder_id);
    }


    /** @test */
    public function admin_can_get_assignment_names_ids_by_course()
    {
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->get("/api/assignments/names-ids-by-course/{$this->course->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function non_admin_cannot_get_assignment_names_ids_by_course()
    {
        $this->actingAs($this->user)
            ->get("/api/assignments/names-ids-by-course/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to get the names and assignment IDs.']);

    }

    /** @test */
    public function non_admin_cannot_get_all_courses()
    {

        $this->actingAs($this->user)
            ->get("/api/courses/all")
            ->assertJson(['message' => 'You are not allowed to get all courses.']);

    }

    /** @test */
    public function admin_can_get_all_courses()
    {
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->get("/api/courses/all")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function non_admin_cannot_update_meta_tags()
    {

        $this->actingAs($this->user)
            ->patch("/api/meta-tags/course/{$this->course->id}/assignment/{$this->assignment->id}", $this->meta_tags_info)
            ->assertJson(['message' => 'You are not allowed to update the meta-tags.']);


    }

    /** @test */
    public function admin_can_update_meta_tags()
    {

        $this->actingAs($this->user)
            ->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email' => $this->admin_user->email])
            ->patch("/api/meta-tags/course/{$this->course->id}/assignment/{$this->assignment->id}", $this->meta_tags_info)
            ->assertJson(['type' => 'success']);

    }


}
