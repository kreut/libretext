<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\LearningTree;
use App\Question;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionEditorTest extends TestCase
{

    /**
     * @var Collection|Model|mixed
     */
    private $question;
    /**
     * @var Collection|Model|mixed
     */
    private $question_editor_user;
    /**
     * @var Collection|Model|mixed
     */
    private $student_user;
    /**
     * @var Collection|Model|mixed
     */
    private $user;
    /**
     * @var string[]
     */
    private $csv_file_array;
    /**
     * @var string[]
     */
    private $question_to_store;
    /**
     * @var Collection|Model|mixed
     */
    private $admin_user;
    /**
     * @var Collection|Model|mixed
     */
    private $default_question_editor_user;
    /**
     * @var \string[][]
     */
    private $exposition_csv_file_array;

    /**
     * @var Collection|Model|mixed
     */


    public function setup(): void
    {
        parent::setUp();
        $this->admin_user = factory(User::class)->create(['id' => 1]);
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->default_question_editor_user = factory(User::class)->create(['role' => 5, 'first_name' => 'Default Non-Instructor Editor']);
        $this->question_editor_user = factory(User::class)->create(['role' => 5]);
        $this->question = factory(Question::class)->create(['library' => 'adapt']);

        $this->csv_file_array = [["Question Type*" => 'assessment',
            "Public*" => "0",
            "Title*" => "Some Title",
            'Source' => 'some source',
            "Auto-Graded Technology" => "webwork",
            "Technology ID/File Path" => "some-file-path",
            "Author" => "",
            "License" => "ccby",
            "License Version" => "this is the license",
            "Tags" => "",
            "Text Question" => "",
            "A11Y Question" => "",
            "Answer" => "",
            "Solution" => "",
            "Hint" => "*"
        ]];

        $this->exposition_csv_file_array = [["Question Type*" => 'exposition',
            "Public*" => "0",
            "Title*" => "Some Title",
            'Source' => 'some source',
            "Auto-Graded Technology" => "",
            "Technology ID/File Path" => "",
            "Author" => "",
            "License" => "ccby",
            "License Version" => "this is the license",
            "Tags" => "",
            "Text Question" => "",
            "A11Y Question" => "",
            "Answer" => "",
            "Solution" => "",
            "Hint" => ""
        ]];

        $this->question_to_store = ['question_type' => 'assessment',
            'public' => 1,
            'title' => 'some title',
            'technology' => 'webwork',
            'technology_id' => 'some file path',
            'tags' => []
        ];
    }

    /** @test */
    public function bulk_upload_of_h5p_questions_cannot_repeat()
    {
        $this->actingAs($this->user)->postJson("/api/questions/h5p/600")
            ->assertJson(['h5p' => ['url' => 'https://studio.libretexts.org/h5p/600']]);

        $this->actingAs($this->user)->postJson("/api/questions/h5p/600")
            ->assertJson(['message' => 'A question already exists with ID 600.']);

    }

    /** @test */
    public function question_cannot_be_deleted_if_in_learning_tree()
    {

        $learning_tree = <<<EOT
{"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"1867"},{"name":"library","value":"query"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 96, 188); left: 327px; top: 145.797px;"}]},{"id":1,"parent":0,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"113448"},{"name":"library","value":"adapt"},{"name":"blockid","value":"1"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid blue; left: 327px; top: 310.797px;"}]}]}
EOT;
        factory(LearningTree::class)->create(['user_id' => $this->user->id, 'learning_tree' => $learning_tree]);
        $this->question->question_editor_user_id = $this->user->id;
        $this->question->save();

        $this->question->page_id = '113448';
        $this->question->library = 'ADAPT';
        $this->question->save();
        $this->actingAs($this->user)->deleteJson("/api/questions/{$this->question->id}")
            ->assertJson(['message' => 'This question already exists in a Learning Tree and cannot be deleted.']);

    }

    /** @test */
    public function question_owner_cannot_edit_a_question_in_another_instructors_assignment()
    {
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store);
        $id = Question::orderBy('id', 'desc')->limit(1)->get()[0]->id;
        $this->question_to_store['id'] = $id;

        $user_2 = factory(User::class)->create();
        $course = factory(Course::class)->create(['user_id' => $user_2->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $assignment->id,
            'question_id' => $id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
        $this->actingAs($this->user)->patchJson("/api/questions/$id", $this->question_to_store)
            ->assertJson(['message' => "You cannot edit this question since it is in another instructor's assignment."]);

    }

    /** @test */
    public function non_question_editor_non_instructor_cannot_upload_bulk_questions()
    {

        $this->actingAs($this->student_user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $this->csv_file_array])
            ->assertJson(['message' => 'You are not allowed to bulk import questions.']);

    }

    /**bulk uploads **/
    /** @test */
    public function uploaded_file_must_not_be_empty()
    {
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => []])
            ->assertJson(['message' => ['The .csv file has no data.']]);
    }

    /** @test */
    public function uploaded_file_must_have_the_right_structure()
    {
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => [['bad structure']]])
            ->assertJson(['message' => ['The CSV should have a first row with the following headings: Question Type*, Public*, Title*, Source, Auto-Graded Technology, Technology ID/File Path, Author, License, License Version, Tags, Text Question, A11Y Question, Answer, Solution, Hint.']]);
    }

    /** @test */
    public function all_rows_need_titles()
    {
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Title*'] = '';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is missing a Title.']]);

    }

    /** @test */
    public function public_should_by_0_or_1()
    {
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Public*'] = 'bogus public';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is missing a valid entry for Public (0 for no and 1 for yes).']]);

    }

    /** @test */
    public function advanced_uploads_need_question_types()
    {
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Question Type*'] = 'Bad Question Type';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 has a Question Type of Bad Question Type but the valid question types are assessment and exposition.']]);
    }

    /** @test */
    public function exposition_questions_need_source()
    {
        $csv_file_array = $this->exposition_csv_file_array;
        $csv_file_array[0]['Question Type*'] = 'exposition';
        $csv_file_array[0]['Source'] = '';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is an exposition type question and is missing the source.']]);

    }

    /** @test */
    public function exposition_questions_and_cannot_have_autograded()
    {
        $csv_file_array = $this->exposition_csv_file_array;
        $csv_file_array[0]['Auto-Graded Technology'] = 'webwork';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is an exposition type question but has an auto-graded technology.']]);

    }

    /** @test */
    public function exposition_questions_and_cannot_have_extra_quetsion_columns()
    {
        $csv_file_array = $this->exposition_csv_file_array;
        $csv_file_array[0]['Text Question'] = 'blah';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is an exposition type question and should not have Text Question, A11Y Question, Answer, Solution, or Hint.']]);

    }

    /** @test */
    public function webwork_questions_need_file_paths()
    {
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Auto-Graded Technology'] = 'webwork';
        $csv_file_array[0]['Technology ID/File Path'] = "";

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 uses webwork and is missing the File Path.']]);

    }

    /** @test */
    public function imathas_and_h5p_need_positive_integers_as_ids()
    {
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Auto-Graded Technology'] = 'imathas';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 uses imathas and requires a positive integer as the Technology ID.']]);
    }

    /** @test */
    public function technology_should_be_valid()
    {
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Auto-Graded Technology'] = 'bogus technology';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is using an invalid technology: bogus technology.']]);

    }

    /** @test */
    public function license_should_be_valid()
    {
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['License'] = 'bogus license';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is using an invalid license: bogus license.']]);

    }

    /** deleting questions */
    /** @test */
    public function question_cannot_be_deleted_by_non_owner()
    {
        $this->question->question_editor_user_id = $this->question_editor_user->id;
        $this->question->save();
        $this->actingAs($this->user)->deleteJson("/api/questions/{$this->question->id}")
            ->assertJson(['message' => 'You are not allowed to delete that question.']);

    }


    /** @test */
    public function question_cannot_be_deleted_if_in_assignment()
    {
        $course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);

        DB::table('assignment_question')->insertGetId([
            'assignment_id' => $assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
        $this->question->question_editor_user_id = $this->user->id;
        $this->question->save();
        $this->actingAs($this->user)->deleteJson("/api/questions/{$this->question->id}")
            ->assertJson(['message' => 'This question already exists in an assignment and cannot be deleted.']);

    }


    /** @test */
    public function owner_can_delete_question()
    {
        $this->question->question_editor_user_id = $this->user->id;
        $this->question->save();
        $this->actingAs($this->user)->deleteJson("/api/questions/{$this->question->id}")
            ->assertJson(['message' => 'The question has been deleted.']);
        $this->assertDatabaseCount('questions', 0);

    }

    /** getting questions */
    /** @test */
    public function only_question_editor_or_instructor_can_get_questions()
    {

        $this->actingAs($this->student_user)->getJson("/api/questions")
            ->assertJson(['message' => 'You are not allowed to view My Questions.']);


    }


    /** storing questions */
    /** @test */
    public function non_question_editor_nor_instructor_cannot_store_questions()
    {

        $this->actingAs($this->student_user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['message' => 'You are not allowed to save questions.']);
    }

    /** @test */
    public function non_question_owner_cannot_edit_the_question()
    {
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store);
        $id = Question::orderBy('id', 'desc')->limit(1)->get()[0]->id;
        $this->question_to_store['id'] = $id;
        $this->actingAs($this->question_editor_user)->patchJson("/api/questions/$id", $this->question_to_store)
            ->assertJson(['message' => 'This is not your question to edit.']);
    }


    /** @test */
    public function storing_a_question_requires_public()
    {
        unset($this->question_to_store['public']);
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJsonValidationErrors('public');

    }

    /** @test */
    public function storing_a_question_requires_a_title()
    {

        unset($this->question_to_store['title']);
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJsonValidationErrors('title');
    }

    /** @test */
    public function exposition_questions_requires_text()
    {
        $this->question_to_store['question_type'] = 'exposition';
        $this->question_to_store['non_technology_text'] = '';
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJsonValidationErrors('non_technology_text');
    }

    /** @test */
    public function auto_graded_questions_require_technology_id()
    {
        $this->question_to_store['technology'] = 'h5p';
        $this->question_to_store['technology_id'] = '';
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJsonValidationErrors(['technology_id']);

    }

    /** @test */
    public function cannot_repeat_technology_id()
    {
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['type' => 'success']);
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJsonValidationErrors(['technology_id']);

    }

    /** @test */
    public function only_question_editor_or_instructor_can_bulk_upload_h5p()
    {

        $this->actingAs($this->user)->postJson("/api/questions/h5p/108")
            ->assertJson(['h5p' => ['url' => 'https://studio.libretexts.org/h5p/108']]);

        $this->actingAs($this->question_editor_user)->postJson("/api/questions/h5p/600")
            ->assertJson(['h5p' => ['url' => 'https://studio.libretexts.org/h5p/600']]);


        $this->actingAs($this->student_user)->postJson("/api/questions/h5p/600")
            ->assertJson(['message' => 'You are not allowed to bulk upload H5P questions.']);

    }


    /** @test */
    public function bulk_upload_h5p_ids_should_be_positive_integers()
    {
        $this->actingAs($this->user)->postJson("/api/questions/h5p/-1")
            ->assertJson(['message' => '-1 is not a valid id.']);

    }

    /** @test */
    public function bulk_upload_h5p_ids_should_be_valid_h5p_ids()
    {

        $this->actingAs($this->user)->postJson("/api/questions/h5p/100000000000")
            ->assertJson(['message' => '100000000000 is not a valid id.']);


    }

    /** @test */
    public function bulk_upload_h5p_returns_h5p_information()
    {

        $this->actingAs($this->user)->postJson("/api/questions/h5p/600")
            ->assertJson(['h5p' => ['url' => 'https://studio.libretexts.org/h5p/600']]);

    }


    /** @test */
    public function non_admin_cannot_delete_question_editors()
    {
        $this->actingAs($this->user)->deleteJson("/api/question-editor/{$this->question_editor_user->id}")
            ->assertJson(['message' => 'You are not allowed to delete that user.']);

    }

    /** @test */
    public function admin_can_delete_question_editors()
    {
        $this->actingAs($this->admin_user)->deleteJson("/api/question-editor/{$this->question_editor_user->id}")
            ->assertJson(['message' => "{$this->question_editor_user->first_name} {$this->question_editor_user->last_name} has been removed and all of their questions have been moved to the Default Question Editor."]);

    }

    /** @test */
    public function one_cannot_delete_the_default_question_editor()
    {
        $this->actingAs($this->admin_user)->deleteJson("/api/question-editor/{$this->default_question_editor_user->id}")
            ->assertJson(['message' => "You cannot delete the default non-instructor editor."]);

    }

    /** @test */
    public function deleted_questions_move_to_the_default_question_editor_user_and_become_public()
    {
        $this->question_to_store['public'] = 0;
        $this->actingAs($this->question_editor_user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['type' => 'success']);
        $this->actingAs($this->admin_user)->deleteJson("/api/question-editor/{$this->question_editor_user->id}")
            ->assertJson(['type' => "success"]);
        $this->assertDatabaseHas('questions', [
            'id' => Question::orderBy('id', 'desc')->first()->id,
            'question_editor_user_id' => $this->default_question_editor_user->id,
            'public' => 1]);

    }


    /** @test */
    public function tags_are_correctly_added()
    {
        $this->question_to_store['tags'] = ['tag 1', 'tag 2', 'tag 3'];
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseCount('question_tag', 3);
        $this->assertDatabaseCount('tags', 3);
        $this->question_to_store['technology_id'] = 'file path 2';
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseCount('question_tag', 6);
        //shouldn't have added more tags
        $this->assertDatabaseCount('tags', 3);

        $this->question_to_store['tags'] = ['tag 1', 'tag 2', 'tag 3', 'tag 4'];
        $this->question_to_store['technology_id'] = 'file path 3';
        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['type' => 'success']);
        //4 more relationships
        $this->assertDatabaseCount('question_tag', 10);
        //one new tag
        $this->assertDatabaseCount('tags', 4);
        $last_submitted_id = Question::where('technology_id', 'file path 3')->first()->id;

        $this->actingAs($this->user)->deleteJson("/api/questions/$last_submitted_id")
            ->assertJson(['type' => 'info']);
        $this->assertDatabaseCount('question_tag', 6);
        $this->assertDatabaseCount('tags', 3);//should only be tag 1, tag 2, tag 3
    }


    /** @test */
    public function non_admin_cannot_view_question_editors()
    {

        $this->actingAs($this->student_user)->getJson("/api/question-editor")
            ->assertJson(['message' => 'You are not allowed to get the question editors.']);

    }

    /** @test */
    public function admin_can_view_question_editors()
    {
        $this->actingAs($this->admin_user)->getJson("/api/question-editor")
            ->assertJson(['type' => 'success']);
    }

}
