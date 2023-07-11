<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Question;
use App\QuestionRevision;
use App\SavedQuestionsFolder;
use App\Tag;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use function env;
use function factory;

class MetaTagTest extends TestCase
{

    /**
     * @var array
     */
    private $meta_tags_course_assignment_info;

    public function setup(): void
    {

        parent::setUp();
        $this->admin_user = factory(User::class)->create(['email' => 'me@me.com']);//Admin
        $this->user = factory(User::class)->create();//not Admin
        $this->user_2 = factory(User::class)->create();//not Admin
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->types = ['instructor', 'non-instructor editor'];
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->folder = factory(SavedQuestionsFolder::class)->create([
            'user_id' => $this->user->id,
            'type' => 'my_questions',
            'name' => 'some_folder'
        ]);

        $this->folder_2 = factory(SavedQuestionsFolder::class)->create([
            'user_id' => $this->user->id,
            'type' => 'my_questions',
            'name' => 'some_folder_2'
        ]);

        $this->question = factory(Question::class)->create(['author' => 'Mike Jones', 'page_id' => 234234]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        $this->question_2 = factory(Question::class)->create(['author' => 'Sam Sampson', 'page_id' => 2342345]);
        $meta_tags_info = [
            'tag_to_remove' => null,
            'tags_to_add' => '',
            'author' => '',
            'license' => null,
            'license_version' => null,
            'apply_to' => $this->question->id
        ];
        $this->meta_tags_course_assignment_info = [];
        $this->meta_tags_my_questions_info = [];

        foreach ($meta_tags_info as $key => $value) {
            $this->meta_tags_course_assignment_info[$key] = $value;
            $this->meta_tags_my_questions_info[$key] = $value;
        }
        $this->meta_tags_course_assignment_info['filter_by'] = ['course_id' => $this->course->id, 'assignment_id' => $this->assignment->id];
        $this->meta_tags_my_questions_info['filter_by'] = ['folder_id' => $this->folder->id];

    }

    /** @test */
    public function when_changing_owner_as_an_instructor_a_pending_question_ownership_transfer_is_added()
    {

        $this->meta_tags_my_questions_info['owner'] = ['value' => $this->user_2->id];
        $this->meta_tags_my_questions_info['filter_by'] = ['folder_id' => 'all'];
        $this->question->question_editor_user_id = $this->user->id;
        $this->question->save();


        $this->actingAs($this->user)
            ->patch("/api/meta-tags", $this->meta_tags_my_questions_info)
            ->assertJson(['message' => 'All meta-tags have been updated except for the owner.  Once the new owner accepts ownership via email, ownership will be transferred.']);
        $this->assertDatabaseHas('pending_question_ownership_transfers', ['new_owner_user_id' => $this->user_2->id]);

    }

    /** @test */
    public function token_must_be_valid_for_pending_question_ownership_transfer()
    {
        $data = ['action' => 'accept', 'token' => 'bogus token'];
        $this->actingAs($this->user)
            ->patch("/api/pending-question-ownership-transfer-request", $data)
            ->assertJson(['message' => 'There are no questions with the associated token.  Please ask the originating instructor to once again update the owner in the Meta-tags page.']);


    }

    /** @test */
    public function accepted_question_ownership_transfer_request_will_transfer_the_ownership_and_delete_the_request()
    {
        $this->meta_tags_my_questions_info['owner'] = ['value' => $this->user_2->id];
        $this->meta_tags_my_questions_info['filter_by'] = ['folder_id' => 'all'];
        $this->question->question_editor_user_id = $this->user->id;
        $this->question->save();


        $this->actingAs($this->user)
            ->patch("/api/meta-tags", $this->meta_tags_my_questions_info)
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('pending_question_ownership_transfers', ['new_owner_user_id' => $this->user_2->id]);
        $token = DB::table('pending_question_ownership_transfers')->first()->token;
        $data = ['action' => 'accept', 'token' => $token];
        $this->actingAs($this->user)
            ->patch("/api/pending-question-ownership-transfer-request", $data)
            ->assertJson(['type' => 'success']);
        $question = DB::table('questions')->where('id', $this->question->id)->first();
        $this->assertEquals($this->user_2->id, $question->question_editor_user_id);
        $this->assertEquals(null, DB::table('pending_question_ownership_transfers')->first());


    }

    /** @test */
    public function rejected_question_ownership_transfer_request_will_not_transfer_the_ownership_and_delete_the_request()
    {

        $this->meta_tags_my_questions_info['owner'] = ['value' => $this->user_2->id];
        $this->meta_tags_my_questions_info['filter_by'] = ['folder_id' => 'all'];
        $this->question->question_editor_user_id = $this->user->id;
        $this->question->save();


        $this->actingAs($this->user)
            ->patch("/api/meta-tags", $this->meta_tags_my_questions_info)
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('pending_question_ownership_transfers', ['new_owner_user_id' => $this->user_2->id]);
        $token = DB::table('pending_question_ownership_transfers')->first()->token;
        $data = ['action' => 'reject', 'token' => $token];
        $this->actingAs($this->user)
            ->patch("/api/pending-question-ownership-transfer-request", $data)
            ->assertJson(['type' => 'success']);
        $question = DB::table('questions')->where('id', $this->question->id)->first();
        $this->assertEquals($this->user->id, $question->question_editor_user_id);
        $this->assertEquals(null, DB::table('pending_question_ownership_transfers')->first());


    }

    /** @test */
    public function cannot_request_ownership_change_if_pending()
    {
        $this->meta_tags_my_questions_info['owner'] = ['value' => $this->user_2->id];
        $this->meta_tags_my_questions_info['filter_by'] = ['folder_id' => 'all'];
        $this->question->question_editor_user_id = $this->user->id;
        $this->question->save();


        $this->actingAs($this->user)
            ->patch("/api/meta-tags", $this->meta_tags_my_questions_info)
            ->assertJson(['type' => 'success']);
        $this->actingAs($this->user)
            ->patch("/api/meta-tags", $this->meta_tags_my_questions_info)
            ->assertJson(['message' => "The following question is in a state of pending ownership transfer: {$this->question->id}.<br><br>If the new owner does not accept transfer of ownership within 24 hours, you can re-request a transfer of question ownership."]);
    }


    /** @test */
    public function choosing_all_folders_changes_all_my_questions()
    {
        $this->question->question_editor_user_id = $this->user->id;
        $this->question->folder_id = $this->folder->id;
        $this->question->save();

        $this->question_2->question_editor_user_id = $this->user->id;
        $this->question_2->folder_id = $this->folder_2->id;
        $this->question_2->save();

        $this->meta_tags_my_questions_info['apply_to'] = 'all';
        $this->meta_tags_my_questions_info['filter_by'] = ['folder_id' => 'all'];
        $this->meta_tags_my_questions_info['author'] = "Stephen King";

        $this->actingAs($this->user)
            ->patch("/api/meta-tags", $this->meta_tags_my_questions_info)
            ->assertJson(['type' => 'success']);
        $num_stephen_kings = Question::where('author', 'Stephen King')->count();
        $this->assertEquals(2, $num_stephen_kings);


    }

    /** @test */
    public function choosing_a_single_folder_changes_all_questions_in_that_folder()
    {

        $this->question->question_editor_user_id = $this->user->id;
        $this->question->folder_id = $this->folder->id;
        $this->question->save();

        $this->question_2->question_editor_user_id = $this->user->id;
        $this->question_2->folder_id = $this->folder_2->id;
        $this->question_2->save();

        $this->meta_tags_my_questions_info['apply_to'] = 'all';
        $this->meta_tags_my_questions_info['filter_by'] = ['folder_id' => $this->folder->id];
        $this->meta_tags_my_questions_info['author'] = "Stephen King";

        $this->actingAs($this->user)
            ->patch("/api/meta-tags", $this->meta_tags_my_questions_info)
            ->assertJson(['type' => 'success']);
        $num_stephen_kings = Question::where('author', 'Stephen King')->count();
        $this->assertEquals(1, $num_stephen_kings);
    }

    /** @test */
    public function must_be_valid_instructor_to_switch_ownership()
    {
        $this->meta_tags_course_assignment_info['owner'] = ['value' => -1];

        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['message' => 'The user new owner must be either an instructor or a non-instructor question editor.']);
    }

    /** @test */
    public function if_is_me_question_editor_user_id_switches_to_new_owner()
    {
        $this->meta_tags_course_assignment_info['owner'] = ['value' => $this->user->id];

        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['type' => 'success']);

        $question = Question::find($this->question->id);

        $this->assertEquals($this->user->id, $question->question_editor_user_id);
        $saved_questions_folder = DB::table('saved_questions_folders')
            ->where('user_id', $this->user->id)
            ->where('type', 'my_questions')
            ->where('name', 'Transferred Questions')
            ->first();
        $this->assertEquals($saved_questions_folder->id, $question->folder_id);
    }

    /** @test */
    public function choosing_all_questions_changes_for_all_questions_in_the_assignment()
    {
        $this->meta_tags_course_assignment_info['apply_to'] = 'all';
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
        $this->meta_tags_course_assignment_info['author'] = "Steven Smith";
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['type' => 'success']);
        $questions = DB::table('questions')->where('author', $this->meta_tags_course_assignment_info['author'])->count();
        $this->assertEquals(count($assignment_questions), $questions);
    }

    /** @test */
    public function choosing_single_question_changes_for_one_question_in_the_assignment()
    {
        $this->meta_tags_course_assignment_info['apply_to'] = $this->question->id;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        $this->meta_tags_course_assignment_info['author'] = "Steven Smith";
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['type' => 'success']);
        $questions = DB::table('questions')->where('author', $this->meta_tags_course_assignment_info['author'])->count();
        $this->assertEquals(1, $questions);
    }


    /** @test */
    public function question_revisions_are_updated_as_well()
    {
        $this->meta_tags_course_assignment_info['apply_to'] = $this->question->id;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        factory(QuestionRevision::class)->create(['action' => 'notify',
            'question_id' => $this->question->id]);
        $question_revisions = DB::table('question_revisions')->where('author', $this->meta_tags_course_assignment_info['author'])->count();
        $this->assertEquals(0, $question_revisions);
        $this->meta_tags_course_assignment_info['author'] = "Steven Smith";
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['type' => 'success']);
        $questions = DB::table('questions')->where('author', $this->meta_tags_course_assignment_info['author'])->count();
        $this->assertEquals(1, $questions);
        $question_revisions = DB::table('question_revisions')->where('author', $this->meta_tags_course_assignment_info['author'])->count();
        $this->assertEquals(1, $question_revisions);
    }

    /** @test */
    public function is_me_can_remove_tags()
    {
        $tag = factory(Tag::class)->create(['tag' => 'some tag']);
        DB::table('question_tag')->insert(['question_id' => $this->question->id, 'tag_id' => $tag->id]);
        $question_tags = DB::table('question_tag')->where('tag_id', $tag->id)->get();
        $this->assertCount(1, $question_tags, 'tag initially exists');
        $this->meta_tags_course_assignment_info['tag_to_remove'] = $tag->id;
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['type' => 'success']);
        $question_tags = DB::table('question_tag')->where('tag_id', $tag->id)->get();
        $this->assertCount(0, $question_tags, 'tag no longer exists');

    }


    /** @test */
    public function is_me_can_add_tags()
    {
        $this->meta_tags_course_assignment_info['tags_to_add'] = "tag 1, tag 2";
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['type' => 'success']);
        $tag_1 = Tag::where('tag', 'tag 1')->first()->id;
        $tag_2 = Tag::where('tag', 'tag 2')->first()->id;
        $question_tags = DB::table('question_tag')->whereIn('tag_id', [$tag_1, $tag_2])->get();
        $this->assertCount(2, $question_tags);
    }

    /** @test */
    public function is_me_can_update_license()
    {

        $this->meta_tags_course_assignment_info['license'] = "new license";
        $this->meta_tags_course_assignment_info['license_version'] = "new license version";
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['type' => 'success']);
        $question = Question::find($this->question->id);
        $this->assertEquals($this->meta_tags_course_assignment_info['license'], $question->license, 'license updated');
        $this->assertEquals($this->meta_tags_course_assignment_info['license_version'], $question->license_version, 'license version updated');

    }


    /** @test */
    public function is_me_can_update_author()
    {
        $this->meta_tags_course_assignment_info['author'] = "Steven Smith";
        $original_question_2_author = Question::find($this->question_2->id)->author;
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['type' => 'success']);
        $question_author = Question::find($this->question->id)->author;
        $this->assertEquals($this->meta_tags_course_assignment_info['author'], $question_author, 'author name changed');

        $question_2_author = Question::find($this->question_2->id)->author;
        $this->assertEquals($original_question_2_author, $question_2_author, 'author name not changed');

    }


    /** @test */
    public function is_me_can_get_assignment_names_ids_by_course()
    {
        $this->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->get("/api/assignments/names-ids-by-course/{$this->course->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function non_is_me_non_instructor_cannot_get_assignment_names_ids_by_course()
    {
        $this->actingAs($this->student_user)
            ->get("/api/assignments/names-ids-by-course/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to get the names and assignment IDs.']);

    }

    /** @test */
    public function non_admin_non_instructor_cannot_get_all_courses()
    {

        $this->actingAs($this->student_user)
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
    public function non_is_me_non_instructor_cannot_update_meta_tags()
    {

        $this->actingAs($this->student_user)
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['message' => 'You are not allowed to update the meta-tags.']);


    }

    /** @test */
    public function is_me_can_update_meta_tags()
    {

        $this->actingAs($this->user)
            ->actingAs($this->admin_user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/meta-tags", $this->meta_tags_course_assignment_info)
            ->assertJson(['type' => 'success']);

    }


}
