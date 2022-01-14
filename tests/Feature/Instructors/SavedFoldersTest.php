<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\MyFavorite;
use App\Question;
use App\SavedQuestionsFolder;
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
    /**
     * @var string[]
     */
    private $saved_question_folder;

    /** Should test that only an instructor can create an assignment... */
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 2]);
        $this->user_2 = factory(User::class)->create(['role' => 2]);
        $this->non_instructor_question_editor =  factory(User::class)->create(['role' => 5]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->saved_question_folder = ['type' => 'my_favorites',
            'name' => 'some name'];
        $this->my_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);
        $this->from_saved_question_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id]);
        $this->move_to_saved_question_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id,
            'name' => 'move to folder']);
        $this->myFavorite = new MyFavorite();
        $this->myFavorite->user_id = $this->user->id;
        $this->myFavorite->folder_id = $this->from_saved_question_folder->id;
        $this->question = factory(Question::class)->create(['page_id' => 1123411329]);
        $this->myFavorite->question_id = $this->question->id;
        $this->myFavorite->open_ended_submission_type = 0;
        $this->myFavorite->save();

    }

    /** @test */
    public function must_own_the_folder_you_are_moving_to_if_deleting()
    {
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders/delete/{$this->from_saved_question_folder->id}",
            ['action' => 'move',
                'move_to_folder_id' => 10000,
                'question_source' => 'my_favorites']
        )
            ->assertJson(['message' => 'You are trying to move the questions to a folder which you do not own.']);
    }

    /** @test */
    public function for_my_favorites_folders_deleting_the_folder_removes_the_questions()
    {
        $num_questions_in_folder  = DB::table('saved_questions_folders')
            ->where('id',$this->from_saved_question_folder->id)
            ->count();
        $this->assertEquals(1, $num_questions_in_folder);
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders/delete/{$this->from_saved_question_folder->id}",
            ['action' => 'delete_without_moving',
                'question_source' => 'my_favorites']
        )
            ->assertJson(['message' => "The folder {$this->from_saved_question_folder->name} has been deleted along with all question in that folder."]);
        $num_questions_in_folder  = DB::table('saved_questions_folders')
            ->where('id',$this->from_saved_question_folder->id)
            ->count();
        $this->assertEquals(0, $num_questions_in_folder);
    }

    /** @test */
    public function cannot_use_delete_option_for_my_questions()
    {
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders/delete/{$this->my_questions_folder->id}",
            ['action' => 'delete_without_moving',
                'question_source' => 'my_questions']
        )
            ->assertJson(['message' => 'These questions must be moved.  They cannot simply be deleted.']);

    }

    /** @test */
    public function questions_are_moved_to_new_folder_if_moving()
    {


        $num_in_old_folder = $this->myFavorite->where('question_id', $this->question->id)
            ->where('folder_id', $this->from_saved_question_folder->id)
            ->count();

        $this->assertEquals(1, $num_in_old_folder);
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders/delete/{$this->from_saved_question_folder->id}",
            ['action' => 'move',
                'move_to_folder_id' => $this->move_to_saved_question_folder->id,
                'question_source' => 'my_favorites']
        )
            ->assertJson(['message' => "The folder {$this->from_saved_question_folder->name} has been deleted and all questions have been moved to {$this->move_to_saved_question_folder->name}."]);
        $num_in_old_folder = $this->myFavorite->where('question_id', $this->question->id)
            ->where('folder_id', $this->from_saved_question_folder->id)
            ->count();
        $this->assertEquals(0, $num_in_old_folder);

        $num_in_new_folder = $this->myFavorite->where('question_id', $this->question->id)
            ->where('folder_id', $this->move_to_saved_question_folder->id)
            ->count();
        $this->assertEquals(1, $num_in_new_folder);
    }

    /** @test */
    public function non_folder_owners_cannot_update_folders()
    {
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJson(['type' => 'success']);
        $saved_question_folder = DB::table('saved_questions_folders')->first();
        $this->saved_question_folder['folder_id'] = $saved_question_folder->id;
        $this->actingAs($this->user_2)->patchJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJson(['message' => 'You are not allowed to update this folder.']);

    }

    /** @test */
    public function folder_owners_can_update_their_own_folders()
    {
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJson(['type' => 'success']);
        $this->saved_question_folder['folder_id'] = $this->from_saved_question_folder->id;
        $this->saved_question_folder['name'] = "New folder name";
        $this->actingAs($this->user)->patchJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJson(['message' => "The folder {$this->from_saved_question_folder->name} has been updated."]);

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
    public function instructors_and_non_instructor_editors_can_create_folders()
    {
        $this->actingAs($this->user)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
            ->assertJson(['type' => 'success']);

        $this->actingAs($this->non_instructor_question_editor)->postJson("/api/saved-questions-folders", $this->saved_question_folder)
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
