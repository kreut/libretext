<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Question;
use App\SubmissionFile;
use App\Submission;
use App\User;
use App\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Carbon\Carbon;

class SavedFoldersTest extends TestCase
{

    /**
     * @var Collection|Model|mixed
     */
    private $user;
    /**
     * @var Collection|Model|mixed
     */
    private $student_user;

    /** Should test that only an instructor can create an assignment... */
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 2]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->student_user = factory(User::class)->create(['role'=> 3]);
        $this->saved_question_folder = ['type' => 'my_favorites',
            'name' => 'some name'];

    }

    /** @test */
    public function folder_names_must_not_be_empty()
    {
        $this->saved_question_folder['name'] = '';
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJsonValidationErrors('name');

    }

    /** @test */
    public function non_instructors_cannot_save_folders()
    {
        $this->actingAs($this->student_user)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJson(['message' => 'You are not allowed to create folders.']);

    }



    /** @test */
    public function folder_names_cannot_repeat()
    {
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJson(['type' => 'success']);
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJsonValidationErrors('name');

    }

    /** @test */
    public function instructors_can_create_folders()
    {
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function instructors_can_update_folders()
    {
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJson(['type' => 'success']);

    }


    /** @test */
    public function non_instructors_cannot_get_saved_folders()
    {
        $this->actingAs($this->student_user)->getJson("/api/saved-questions-folders/my_questions")
            ->assertJson(['message' => 'You are not allowed to retrieve folders.']);

    }

    /** @test */
    public function folder_type_must_be_valid()
    {
        $this->actingAs($this->user)->getJson("/api/saved-questions-folders/bad_folder_type")
            ->assertJson(['message' => 'bad_folder_type is not a valid type.']);

    }

    /** @test */
    public function instructors_can_get_saved_folders()
    {
        $this->actingAs($this->user)->getJson("/api/saved-questions-folders/my_questions",)
            ->assertJson(['type' => 'success']);

    }


}
