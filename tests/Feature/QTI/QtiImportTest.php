<?php

namespace Tests\Feature\QTI;

use App\AssignmentTemplate;
use App\Course;
use App\SavedQuestionsFolder;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use function factory;

class QtiImportTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment_template = factory(AssignmentTemplate::class)->create(['user_id' => $this->user->id]);
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);
        $this->directory = 'test directory';
        $this->filename = 'some filename';


        $this->qti_job_id = DB::table('qti_jobs')->insertGetId([
            'user_id' => $this->user->id,
            'qti_source' => 'canvas',
            'public' => 1,
            'folder_id' => factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id])->id,
            'license' => 'Public domain',
            'qti_directory' => $this->directory]);

        $this->qti_file_info = ['directory' => $this->directory,
            'filename' => $this->filename,
            'author' => "{$this->user->first_name} {$this->user->last_name}",
            'folder_id' => $this->saved_questions_folder->id,
            'license' => 'some license',
            'qti_source' => 'canvas',
            'import_template' => 'qti',
            'license_version' => null];



    }



    /** @test **/
    public function only_instructors_can_import_qti()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            $this->qti_file_info)
            ->assertJson(['message' => "You are not allowed to bulk import questions."]);
    }

    /** @test **/
    public function must_own_course()
    {
        $this->qti_file_info['import_to_course'] = 1232131321312;
        $error['message']['form_errors']['import_to_course'] = 'That is not one of your courses.';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            $this->qti_file_info)
            ->assertJson($error);

    }

    /** @test **/
    public function must_choose_an_assignment_template()
    {
        $this->qti_file_info['import_to_course'] = $this->course->id;
        $error['message']['form_errors']['assignment_template'] = 'Please choose an assignment template.';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            $this->qti_file_info)
            ->assertJson($error);

    }


    /** @test **/
    public function must_own_assignment_template()
    {
        $this->qti_file_info['import_to_course'] = $this->course->id;
        $this->qti_file_info['assignment_template'] = 'fake assignment template id';
        $error['message']['form_errors']['assignment_template'] = 'That is not one of your assignment templates.';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            $this->qti_file_info)
            ->assertJson($error);

    }

    /** @test **/
    public function author_is_required()
    {
        unset($this->qti_file_info['author']);
        $error['message']['form_errors']['author'] = "An author is required.";
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            $this->qti_file_info)
            ->assertJson($error);
    }

    /** @test **/
    public function folder_is_required()
    {
        unset($this->qti_file_info['folder_id']);
        $error['message']['form_errors']['folder_id']  = "Please select a folder.";
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            $this->qti_file_info)
            ->assertJson($error);
    }

    /** @test **/
    public function must_own_folder()
    {
        $this->qti_file_info['folder_id'] = 12831238;
        $error['message']['form_errors']['folder_id']  = "That is not one of your folders.";
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            $this->qti_file_info)
            ->assertJson($error);
    }

    /** @test **/
    public function license_is_required()
    {
        unset($this->qti_file_info['license']);
        $error['message']['form_errors']['license'] = "A license is required.";
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            $this->qti_file_info)
            ->assertJson($error);
    }


}
