<?php

namespace Tests\Feature\General;

use App\Assignment;
use App\AssignmentTemplate;
use App\AssignmentTopic;
use App\BetaCourse;
use App\Course;
use App\Enrollment;
use App\LearningTree;
use App\Question;
use App\SavedQuestionsFolder;
use App\Section;
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
            "Folder*" => 'Some Folder',
            "Title*" => "Some Title",
            "Assignment" => "",
            "Template" => "",
            "Topic" => "",
            'Header HTML' => 'some source',
            "Auto-Graded Technology" => "webwork",
            "Technology ID/File Path" => "some-file-path",
            "Author*" => "some author",
            "License*" => "ccby",
            "License Version" => "this is the license",
            "Source URL" => "",
            "Tags" => "",
            "Text Question" => "",
            "Answer" => "",
            "Solution" => "",
            "Hint" => "*"
        ]];

        $this->csv_file_array_my_questions = [["Question Type*" => 'assessment',
            "Public*" => "0",
            "Folder*" => 'Some Folder',
            "Title*" => "Some Title",
            'Header HTML' => 'some source',
            "Auto-Graded Technology" => "webwork",
            "Technology ID/File Path" => "some-file-path",
            "Author*" => "Some author",
            "License*" => "ccby",
            "License Version" => "this is the license",
            "Source URL" => "",
            "Tags" => "",
            "Text Question" => "",
            "Answer" => "",
            "Solution" => "",
            "Hint" => "*"
        ]];

        $this->exposition_csv_file_array = [[
            "Question Type*" => 'exposition',
            "Public*" => "0",
            "Folder*" => 'Some Folder',
            "Title*" => "Some Title",
            "Assignment" => "",
            "Template" => "",
            "Topic" => "",
            'Header HTML' => 'some source',
            "Auto-Graded Technology" => "",
            "Technology ID/File Path" => "",
            "Author*" => "Some Author",
            "License*" => "ccby",
            "License Version" => "this is the license",
            "Source URL" => "",
            "Tags" => "",
            "Text Question" => "",
            "Answer" => "",
            "Solution" => "",
            "Hint" => ""
        ]];

        $this->exposition_csv_file_array_my_questions = [[
            "Question Type*" => 'exposition',
            "Public*" => "0",
            "Folder*" => 'Some Folder',
            "Title*" => "Some Title",
            'Header HTML' => 'some source',
            "Auto-Graded Technology" => "",
            "Technology ID/File Path" => "",
            "Author*" => "Some Author",
            "License*" => "ccby",
            "License Version" => "this is the license",
            "Source URL" => "",
            "Tags" => "",
            "Text Question" => "",
            "Answer" => "",
            "Solution" => "",
            "Hint" => ""
        ]];
        $this->my_questions_folder = factory(SavedQuestionsFolder::class)->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Some Folder',
                'type' => 'my_questions'
            ]
        );
        $this->question_to_store = ['question_type' => 'assessment',
            'public' => 1,
            'title' => 'some title',
            'technology' => 'webwork',
            'technology_id' => 'some file path',
            'author' => 'some author',
            'license' => 'publicdomain',
            'tags' => [],
            'folder_id' => $this->my_questions_folder->id
        ];
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment_template = factory(AssignmentTemplate::class)->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function if_repeat_bulk_upload_of_h5p_questions_will_save_to_my_favorites_folder()
    {
        $this->actingAs($this->user)->postJson("/api/questions/h5p/600", ['folder_id' => $this->my_questions_folder->id])
            ->assertJson(['h5p' => ['url' => 'https://studio.libretexts.org/h5p/600']]);

        $this->actingAs($this->user)->postJson("/api/questions/h5p/600", ['folder_id' => $this->my_questions_folder->id])
            ->assertJson(['type' => 'success'])
            ->assertJson(['h5p' => ['title' => "_D03_a01_Energy_Kinetic_Classical (Already exists in ADAPT, but added to your My Favorites folder 'Main')"]]);

    }

    /** @test */
    public function if_an_assignment_does_not_exist_a_template_must_be_provided()
    {

        $this->csv_file_array[0]['Assignment'] = "Some assignment which does not exist";

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $this->csv_file_array])
            ->assertJson(['message' => ["Row 2 has an assignment which is not in {$this->course->name}. In addition, there is no Template that can be used to create an assignment."]]);

    }

    /** @test */
    public function if_an_assignment_does_not_exist_it_is_created_using_the_template()
    {
        $assignment_name = "Some assignment that does not exist";
        $this->csv_file_array[0]['Assignment'] = $assignment_name;
        $this->csv_file_array[0]['Template'] = $this->assignment_template->template_name;

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $this->csv_file_array])
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('assignments', ['name' => $assignment_name]);
    }

    /** @test */
    public function template_must_not_change_for_the_same_assignment()
    {

        $this->csv_file_array[0]['Assignment'] = "Some assignment";
        $this->csv_file_array[0]['Template'] = $this->assignment_template->template_name;
        $csv_file_array[0] = $this->csv_file_array[0];
        $this->csv_file_array[0]['Template'] = "Some other template which does not exist";
        $csv_file_array[1] = $this->csv_file_array[0];
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 3 has an Assignment Some assignment and a Template Some other template which does not exist but a previous row has the same Assignment with a different Template.']]);
    }

    /** @test */
    public function can_add_question_without_a_topic()
    {
        $this->my_questions_folder->user_id = $this->question_editor_user->id;
        $this->my_questions_folder->save();
        $course = factory(Course::class)->create(['user_id' => $this->question_editor_user->id]);

        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);

        $this->question_to_store['course_id'] = $course->id;
        $this->question_to_store['assignment'] = $assignment->name;

        $this->actingAs($this->question_editor_user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('assignment_question', [
            'assignment_id' => $assignment->id, 'assignment_topic_id' => null
        ]);
    }

    /** @test */
    public function can_create_a_folder_during_bulk_import_if_it_does_not_exist()
    {
        $assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Assignment'] = $assignment->name;
        $csv_file_array[0]['Topic'] = "Some Topic";
        $csv_file_array[0]['Folder*'] = "The best folder ever";
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $csv_file_array])
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('saved_questions_folders', [
            'name' => 'The best folder ever',
            'type' => 'my_questions',
            'user_id' => $this->user->id]);
    }


    /** @test */
    public function question_gets_added_with_the_topic_if_it_exists()
    {

        $this->my_questions_folder->user_id = $this->question_editor_user->id;
        $this->my_questions_folder->save();
        $course = factory(Course::class)->create(['user_id' => $this->question_editor_user->id]);

        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);

        $assignmentTopic = new AssignmentTopic();
        $assignmentTopic->name = 'some topic';
        $assignmentTopic->assignment_id = $assignment->id;
        $assignmentTopic->save();

        $this->question_to_store['course_id'] = $course->id;
        $this->question_to_store['assignment'] = $assignment->name;
        $this->question_to_store['topic'] = $assignmentTopic->name;
        $this->actingAs($this->question_editor_user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('assignment_question',
            ['assignment_id' => $assignment->id,
                'assignment_topic_id' => $assignmentTopic->id
            ]);

    }


    /** @test */
    public function cannot_bulk_upload_into_a_course_with_beta_courses()
    {
        $csv_file_array = $this->csv_file_array;


        $beta_course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $betaCourse = new BetaCourse();
        $betaCourse->alpha_course_id = $this->course->id;
        $betaCourse->id = $beta_course->id;
        $betaCourse->save();

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ["Bulk upload is not possible for Alpha courses which already have Beta courses.  You can always make a copy of the course and upload these questions to the copied course."]]);

    }

    /** @test */
    public function if_course_then_needs_assignment()
    {
        $csv_file_array = $this->csv_file_array;
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is missing an Assignment.']]);

    }

    /** @test */
    public function cannot_bulk_upload_into_a_course_with_enrollments()
    {
        $csv_file_array = $this->csv_file_array;

        $section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $section->id,
            'course_id' => $this->course->id
        ]);

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ["Bulk upload is only possible for courses without any enrollments.  Please make a copy of the course and upload these questions to the copied course."]]);

    }

    /** @test */
    public function if_course_assignment_and_topic_then_must_belong_to_owner_when_storing_question()
    {
        $this->my_questions_folder->user_id = $this->question_editor_user->id;
        $this->my_questions_folder->save();
        $course = factory(Course::class)->create(['user_id' => $this->question_editor_user->id]);

        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);

        $course_2 = factory(Course::class)->create(['user_id' => $this->user->id]);
        $assignment_2 = factory(Assignment::class)->create(['course_id' => $course_2->id]);
        $assignmentTopic = new AssignmentTopic();
        $assignmentTopic->name = 'some topic';
        $assignmentTopic->assignment_id = $assignment_2->id;
        $assignmentTopic->save();

        $this->question_to_store['course_id'] = $course->id;
        $this->question_to_store['assignment'] = $assignment->name;
        $this->question_to_store['topic'] = $assignmentTopic->name;
        $this->actingAs($this->question_editor_user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['errors' => ['folder_id' => ['You do not own that combination of Course, Assignment, Topic.']]]);

    }


    /** @test */
    public function can_store_if_course_and_assignment_belong_to_owner()
    {
        $this->my_questions_folder->user_id = $this->question_editor_user->id;
        $this->my_questions_folder->save();
        $course = factory(Course::class)->create(['user_id' => $this->question_editor_user->id]);
        $this->question_to_store['course_id'] = $course->id;
        $assignment = factory(Assignment::class)->create(['course_id' => $course->id]);
        $this->question_to_store['assignment'] = $assignment->name;
        $this->actingAs($this->question_editor_user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function if_course_and_assignment_then_must_belong_to_owner_when_storing_question()
    {
        $this->my_questions_folder->user_id = $this->question_editor_user->id;
        $this->my_questions_folder->save();
        $this->question_to_store['course_id'] = 1249812;
        $this->actingAs($this->question_editor_user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['errors' => ['folder_id' => ['You do not own that combination of Course and Assignment.']]]);
        $course = factory(Course::class)->create(['user_id' => $this->question_editor_user->id]);
        $this->question_to_store['course'] = $course->name;
        $this->actingAs($this->question_editor_user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['errors' => ['folder_id' => ['You do not own that combination of Course and Assignment.']]]);

    }


    /** @test */
    public function cannot_have_a_topic_without_a_course_and_assignment()
    {
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Topic'] = 'Some topic';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is missing an Assignment.']]);

    }


    /** @test */

    public function course_should_be_one_of_your_courses()
    {
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Assignment'] = 'Some assignment';
        $course = factory(Course::class)->create(['user_id' => $this->question_editor_user->id]);
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $course->id,
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => "You are not allowed to bulk import questions into a course that you don't own."]);
    }

    /** @test */

    public function assignment_should_exist_in_the_course()
    {

        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Assignment'] = 'Bad assignment';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ["Row 2 has an assignment which is not in {$this->course->name}. In addition, there is no Template that can be used to create an assignment."]]);
    }


    /** @test */

    public function can_create_a_topic_if_it_does_not_exist()
    {
        $assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $csv_file_array = $this->csv_file_array;
        $csv_file_array[0]['Assignment'] = $assignment->name;
        $csv_file_array[0]['Topic'] = "Some Topic";
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'course_id' => $this->course->id,
                'csv_file_array' => $csv_file_array])
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('assignment_topics', ['name' => 'Some Topic', 'assignment_id' => $assignment->id]);
    }


    /** @test */
    public function deleted_questions_move_to_the_default_question_editor_user_and_become_public()
    {
        $this->question_to_store['public'] = 0;
        $this->my_questions_folder->user_id = $this->question_editor_user->id;
        $this->my_questions_folder->save();
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
    public function only_question_editor_or_instructor_can_bulk_upload_h5p()
    {

        $this->actingAs($this->user)->postJson("/api/questions/h5p/108", ['folder_id' => $this->my_questions_folder->id])
            ->assertJson(['h5p' => ['url' => 'https://studio.libretexts.org/h5p/108']]);

        $this->my_questions_folder->user_id = $this->question_editor_user->id;
        $this->my_questions_folder->save();
        $this->actingAs($this->question_editor_user)->postJson("/api/questions/h5p/600", ['folder_id' => $this->my_questions_folder->id])
            ->assertJson(['h5p' => ['url' => 'https://studio.libretexts.org/h5p/600']]);

        $this->actingAs($this->student_user)->postJson("/api/questions/h5p/600", ['folder_id' => $this->my_questions_folder->id])
            ->assertJson(['message' => 'You are not allowed to bulk upload H5P questions.']);

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
        return true;


        $this->actingAs($this->user)->postJson("/api/questions", $this->question_to_store)
            ->assertJson(['type' => 'success']);
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
        $this->question_to_store['folder_id'] = $this->my_questions_folder->id;

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
            ->assertJson(['message' => ['The CSV should have a first row with the following headings: Question Type*, Public*, Folder*, Title*, Header HTML, Auto-Graded Technology, Technology ID/File Path, Author*, License*, License Version, Source URL, Tags, Text Question, Answer, Solution, Hint.']]);
    }

    /** @test */
    public function all_rows_need_titles()
    {
        $csv_file_array = $this->csv_file_array_my_questions;
        $csv_file_array[0]['Title*'] = '';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is missing a Title.']]);

    }

    /** @test */
    public function all_rows_need_folders()
    {
        $csv_file_array = $this->csv_file_array_my_questions;
        $csv_file_array[0]['Folder*'] = '';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is missing a Folder.']]);

    }

    /** @test */
    public function public_should_by_0_or_1()
    {
        $csv_file_array = $this->csv_file_array_my_questions;
        $csv_file_array[0]['Public*'] = 'bogus public';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is missing a valid entry for Public (0 for no and 1 for yes).']]);

    }

    /** @test */
    public function advanced_uploads_need_question_types()
    {
        $csv_file_array = $this->csv_file_array_my_questions;
        $csv_file_array[0]['Question Type*'] = 'Bad Question Type';
        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 has a Question Type of Bad Question Type but the valid question types are assessment and exposition.']]);
    }

    /** @test */
    public function exposition_questions_need_source()
    {
        $csv_file_array = $this->exposition_csv_file_array_my_questions;
        $csv_file_array[0]['Question Type*'] = 'exposition';
        $csv_file_array[0]['Header HTML'] = '';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is an exposition type question and is missing the source.']]);

    }

    /** @test */
    public function exposition_questions_and_cannot_have_autograded()
    {
        $csv_file_array = $this->exposition_csv_file_array_my_questions;
        $csv_file_array[0]['Auto-Graded Technology'] = 'webwork';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is an exposition type question but has an auto-graded technology.']]);

    }

    /** @test */
    public function exposition_questions_and_cannot_have_extra_quetsion_columns()
    {
        $csv_file_array = $this->exposition_csv_file_array_my_questions;
        $csv_file_array[0]['Text Question'] = 'blah';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is an exposition type question and should not have Text Question, Answer, Solution, or Hint.']]);

    }

    /** @test */
    public function webwork_questions_need_file_paths()
    {
        $csv_file_array = $this->csv_file_array_my_questions;
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
        $csv_file_array = $this->csv_file_array_my_questions;
        $csv_file_array[0]['Auto-Graded Technology'] = 'imathas';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 uses imathas and requires a positive integer as the Technology ID.']]);
    }

    /** @test */
    public function technology_should_be_valid()
    {
        $csv_file_array = $this->csv_file_array_my_questions;
        $csv_file_array[0]['Auto-Graded Technology'] = 'bogus technology';

        $this->actingAs($this->user)->putJson("/api/questions/validate-bulk-import-questions",
            ['import_template' => 'advanced',
                'csv_file_array' => $csv_file_array])
            ->assertJson(['message' => ['Row 2 is using an invalid technology: bogus technology.']]);

    }

    /** @test */
    public function license_should_be_valid()
    {
        $csv_file_array = $this->csv_file_array_my_questions;
        $csv_file_array[0]['License*'] = 'bogus license';

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
        $question = Question::orderBy('id', 'desc')->limit(1)->get()[0];
        $id = $question->id;
        $question_author = User::find($question->question_editor_user_id);
        $this->question_to_store['id'] = $id;
        $this->actingAs($this->question_editor_user)->patchJson("/api/questions/$id", $this->question_to_store)
            ->assertJson(['message' => "This is not your question to edit. This question is owned by $question_author->first_name $question_author->last_name."]);
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
    public function bulk_upload_h5p_ids_should_be_positive_integers()
    {
        $this->actingAs($this->user)->postJson("/api/questions/h5p/-1", ['folder_id' => $this->my_questions_folder->id])
            ->assertJson(['message' => '-1 is not a valid id.']);

    }

    /** @test */
    public function bulk_upload_h5p_ids_should_be_valid_h5p_ids()
    {

        $this->actingAs($this->user)->postJson("/api/questions/h5p/100000000000", ['folder_id' => $this->my_questions_folder->id])
            ->assertJson(['message' => '100000000000 is not a valid id.']);


    }

    /** @test */
    public function bulk_upload_h5p_returns_h5p_information()
    {

        $this->actingAs($this->user)->postJson("/api/questions/h5p/600", ['folder_id' => $this->my_questions_folder->id])
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
