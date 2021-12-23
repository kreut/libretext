<?php

namespace Tests\Feature\Instructors;


use App\Traits\Test;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\GraderAccessCode;
use App\Score;
use App\Section;
use App\Submission;
use App\SubmissionFile;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SectionsTest extends TestCase
{
    use Test;

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


    /** @test */
    public function the_main_section_cannot_be_removed()
    {

        $this->actingAs($this->user)->deleteJson("api/sections/{$this->section->id}")
            ->assertJson(['message' => 'The first section cannot be removed.']);
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
        $this->assertEquals(1, $section_graders, 'original graders');

        $this->actingAs($this->user)->deleteJson("api/sections/{$this->section_1->id}")
            ->assertJson(['message' => '<strong>Section 1</strong> has been deleted.']);

        $section_1_submissions = Submission::whereIn('user_id', $section_1_user_ids)->get()->count();
        $section_2_submissions = Submission::whereIn('user_id', $section_2_user_ids)->get()->count();

        $this->assertEquals(1, $section_1_submissions, 'new section 1 submissions');
        $this->assertEquals(0, $section_2_submissions, 'new section 2 submissions');

        $section_1_submission_files = SubmissionFile::whereIn('user_id', $section_1_user_ids)->get()->count();
        $section_2_submission_files = SubmissionFile::whereIn('user_id', $section_2_user_ids)->get()->count();

        $this->assertEquals(1, $section_1_submission_files, 'new section 1 submission files');
        $this->assertEquals(0, $section_2_submission_files, 'new section 2 submission files');

        $section_1_scores = Score::whereIn('user_id', $section_1_user_ids)->get()->count();
        $section_2_scores = Score::whereIn('user_id', $section_2_user_ids)->get()->count();

        $this->assertEquals(1, $section_1_scores, 'new section 1 scores');
        $this->assertEquals(0, $section_2_scores, 'new section 2 scores');

        $course_enrollments = Enrollment::where('course_id', $this->course->id)->get()->count();

        $this->assertEquals(1, $course_enrollments, 'new course enrollments');
        $section_graders = Grader::where('section_id', $this->section_1->id)->get()->count();
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

    public function commons_owner_cannot_create_a_section()
    {
        $this->user_2->email = 'commons@libretexts.org';
        $this->user_2->save();
        $this->actingAs($this->user_2)->postJson("/api/sections/{$this->course->id}", $this->section_info)
            ->assertJson(['message' => 'You are not allowed to create a section for this course.']);
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
