<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\GraderAccessCode;
use App\Question;
use App\Score;
use App\Section;
use App\Submission;
use App\SubmissionFile;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SectionsTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->section_1 = factory(Section::class)->create(['course_id' => $this->course->id]);


        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user_2->id]);
        $this->section_2 = factory(Section::class)->create(['course_id' => $this->course_2->id]);

        $this->course_3 = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section_3 = factory(Section::class)->create(['course_id' => $this->course_3->id]);

        $this->grader_user = factory(User::class)->create();
        $this->grader_user->role = 4;
        Grader::create(['user_id' => $this->grader_user->id, 'section_id' => $this->section->id]);
        Grader::create(['user_id' => $this->grader_user->id, 'section_id' => $this->section_2->id]);
        GraderAccessCode::create(['section_id' => $this->section_3->id, 'access_code' => 'sdfsdOlwf']);
        $this->section_info = ['name' => 'New Section',
            'course_id' => $this->course->id,
            'crn' => 'some CRN'];

    }

    public function createStudentUsers()
    {
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->student_user->save();
        Enrollment::create(['course_id' => $this->course->id,
            'section_id' => $this->section->id,
            'user_id' => $this->student_user->id]);

        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;
        $this->student_user_2->save();
        Enrollment::create(['course_id' => $this->course->id,
            'section_id' => $this->section_1->id,
            'user_id' => $this->student_user_2->id]);
    }

    public function createAssignmentQuestions()
    {
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create(['page_id' => 3]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 4]);
        $this->question_points = 10;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);
    }

    public function createSubmissions()
    {
        $data = [
            'type' => 'q',
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => 'fake_1.pdf',
            'original_filename' => 'orig_fake_1.pdf',
            'date_submitted' => Carbon::now()];
        SubmissionFile::create($data);
        $data['user_id'] = $this->student_user_2->id;
        SubmissionFile::create($data);

        $this->h5pSubmission = [
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'score' => '0.00',
            'answered_correctly_at_least_once' => 0,
            'submission_count' => 1,
            'submission' => '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}'
        ];
        Submission::create($this->h5pSubmission);
        $this->h5pSubmission['user_id'] = $this->student_user_2->id;

        Submission::create($this->h5pSubmission);
    }

    public function createScores()
    {
        Score::create(['assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user->id,
            'score' => 10]);
        Score::create(['assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user_2->id,
            'score' => 10]);

    }

    public function addGraders()
    {
        $this->grader_user = factory(User::class)->create();
        $this->grader_user->role = 4;
        Grader::create(['user_id' => $this->grader_user->id, 'section_id' => $this->section->id]);
    }

    /** @test * */
    public function owner_can_delete_a_section()
    {
        $this->createStudentUsers();
        $this->addGraders();
        $this->createAssignmentQuestions();
        $this->createSubmissions();
        $this->createScores();

        $section_1_user_ids = Enrollment::where('section_id', $this->section->id)
            ->get()
            ->pluck('user_id')
            ->toArray();

        $section_2_user_ids = Enrollment::where('section_id', $this->section_1->id)
            ->get()
            ->pluck('user_id')
            ->toArray();

        $section_1_submissions = Submission::whereIn('user_id', $section_1_user_ids)->get()->count();
        $section_2_submissions = Submission::whereIn('user_id', $section_2_user_ids)->get()->count();

        $this->assertEquals(1, $section_1_submissions, 'original submissions section 1');
        $this->assertEquals(1, $section_2_submissions, 'original submissions section 2');

        $section_1_submission_files = SubmissionFile::whereIn('user_id', $section_1_user_ids)->get()->count();
        $section_2_submission_files = SubmissionFile::whereIn('user_id', $section_2_user_ids)->get()->count();

        $this->assertEquals(1, $section_1_submission_files, 'original file submissions section 1');
        $this->assertEquals(1, $section_2_submission_files, 'original file submissions section 2');

        $section_1_scores = Score::whereIn('user_id', $section_1_user_ids)->get()->count();
        $section_2_scores = Score::whereIn('user_id', $section_2_user_ids)->get()->count();

        $this->assertEquals(1, $section_1_scores, 'original scores section 1');
        $this->assertEquals(1, $section_2_scores, 'original scores section 2');

        $course_enrollments = Enrollment::where('course_id', $this->course->id)->get()->count();

        $this->assertEquals(2, $course_enrollments, 'original course enrollments');
        $section_graders = Grader::where('section_id', $this->section->id)->get()->count();
        $this->assertEquals(2, $section_graders, 'original graders');

        $this->actingAs($this->user)->deleteJson("api/sections/{$this->section->id}")
            ->assertJson(['message' => '<strong>Section 1</strong> has been deleted.']);

        $section_1_submissions = Submission::whereIn('user_id', $section_1_user_ids)->get()->count();
        $section_2_submissions = Submission::whereIn('user_id', $section_2_user_ids)->get()->count();

        $this->assertEquals(0, $section_1_submissions, 'new section 1 submissions');
        $this->assertEquals(1, $section_2_submissions,'new section 2 submissions', );

        $section_1_submission_files = SubmissionFile::whereIn('user_id', $section_1_user_ids)->get()->count();
        $section_2_submission_files = SubmissionFile::whereIn('user_id', $section_2_user_ids)->get()->count();

        $this->assertEquals(0, $section_1_submission_files, 'new section 1 submission files');
        $this->assertEquals(1, $section_2_submission_files, 'new section 2 submission files');

        $section_1_scores = Score::whereIn('user_id', $section_1_user_ids)->get()->count();
        $section_2_scores = Score::whereIn('user_id', $section_2_user_ids)->get()->count();

        $this->assertEquals(0, $section_1_scores, 'new section 1 scores');
        $this->assertEquals(1, $section_2_scores, 'new section 2 scores');

        $course_enrollments = Enrollment::where('course_id', $this->course->id)->get()->count();

        $this->assertEquals(1, $course_enrollments, 'new course enrollments');
        $section_graders = Grader::where('section_id', $this->section->id)->get()->count();
        $this->assertEquals(0, $section_graders, 'new section 2 graders');


    }

    /** @test * */
    public function non_owner_cannot_delete_a_section()
    {


        $this->actingAs($this->user_2)->deleteJson("api/sections/{$this->section->id}")
            ->assertJson(['message' => 'You are not allowed to delete this section.']);

    }


    /** @test */
    public function non_owner_cannot_refresh_a_section_access_code()
    {

        $this->actingAs($this->user_2)->patchJson("api/sections/refresh-access-code/{$this->section->id}")
            ->assertJson(['message' => 'You are not allowed to refresh access codes for that section.']);

    }


    /** @test */
    public function owner_can_refresh_a_section_access_code()
    {
        $this->actingAs($this->user)->patchJson("api/sections/refresh-access-code/{$this->section->id}")
            ->assertJson(['message' => 'The access code has been refreshed.']);

    }



    /** @test */

    public function non_owner_cannot_create_a_section()
    {

        $this->actingAs($this->user_2)->postJson("/api/sections/{$this->course->id}", $this->section_info)
            ->assertJson(['message' => 'You are not allowed to create a section for this course.']);
    }

    /** @test */

    public function section_name_must_be_valid()
    {
        $this->section_info['name'] = '';
        $this->actingAs($this->user)->postJson("/api/sections/{$this->course->id}", $this->section_info)
            ->assertJsonValidationErrors(['name']);

    }


    /** @test */

    public function owner_can_create_a_new_section()
    {
        $this->actingAs($this->user)->postJson("/api/sections/{$this->course->id}", $this->section_info)
            ->assertJson(['message' => 'The section <strong>New Section</strong> has been created.']);

    }

    /** @test */
    public function non_owner_cannot_edit_a_section()
    {

        $this->actingAs($this->user_2)->patchJson("/api/sections/{$this->section->id}", $this->section_info)
            ->assertJson(['message' => 'You are not allowed to update this section.']);

    }

    /** @test */
    public function owner_can_edit_a_section()
    {

        $this->section_info['name'] = 'new name';
        $this->actingAs($this->user)->patchJson("/api/sections/{$this->section->id}", $this->section_info)
            ->assertJson(['type' => 'success']);
        $section_name = Section::find($this->section->id)->name;
        $this->assertEquals('new name', $section_name);

    }



}
