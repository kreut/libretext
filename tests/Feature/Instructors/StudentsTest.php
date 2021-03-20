<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\AssignToUser;
use App\Course;
use App\Enrollment;
use App\Extension;
use App\ExtraCredit;
use App\LtiGradePassback;
use App\Question;
use App\Score;
use App\Section;
use App\Seed;
use App\Submission;
use App\SubmissionFile;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

use App\Traits\Test;

class StudentsTest extends TestCase
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

        $this->fake_student_user_1 = factory(User::class)->create();
        $this->fake_student_user_1->role = 3;
        $this->fake_student_user_1->fake_student = 1;
        $this->fake_student_user_1->save();

        factory(Enrollment::class)->create([
            'user_id' => $this->fake_student_user_1->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        $this->fake_student_user_2 = factory(User::class)->create();
        $this->fake_student_user_2->role = 3;
        $this->fake_student_user_2->fake_student = 1;
        $this->fake_student_user_2->save();

        factory(Enrollment::class)->create([
            'user_id' => $this->fake_student_user_2->id,
            'section_id' => $this->section_1->id,
            'course_id' => $this->course->id
        ]);


        $this->question = factory(Question::class)->create(['page_id' => 1]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);


        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user_2->id]);
        $this->section_2 = factory(Section::class)->create(['course_id' => $this->course_2->id]);
        $this->student_user_3 = factory(User::class)->create();
        $this->student_user_3->role = 3;
        $this->student_user_3->save();

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user_3->id,
            'section_id' => $this->section_2->id,
            'course_id' => $this->course_2->id
        ]);
        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user_2->id]);
        $this->section_2 = factory(Section::class)->create(['course_id' => $this->course->id]);


        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->student_user->save();
        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;
        $this->student_user_2->save();


        foreach ([$this->student_user->id, $this->student_user_2->id] as $student_id) {
            Extension::create(['user_id' => $student_id,
                'assignment_id' => $this->assignment->id,
                'extension' => '2020-10-23 18:00:00']);
            Score::create(['user_id' => $student_id,
                'assignment_id' => $this->assignment->id,
                'score' => 10]);
            LtiGradePassback::create(['user_id' => $student_id,
                'assignment_id' => $this->assignment->id,
                'launch_id' => 1,
                'success' => true,
                'message' => 'blah']);
            Seed::create(['user_id' => $student_id,
                'assignment_id' => $this->assignment->id,
                'question_id' => $this->question->id,
                'seed' => 1234]);
            ExtraCredit::create(['user_id' => $student_id,
                'course_id' => $this->course->id,
                'extra_credit' => 5]);

            factory(Enrollment::class)->create([
                'user_id' => $student_id,
                'section_id' => $this->section->id,
                'course_id' => $this->course->id
            ]);

            $this->assignUserToAssignment($this->assignment->id, 'section', $this->section->id, $student_id);
            Submission::create(['assignment_id' => $this->assignment->id,
                'question_id' => $this->question->id,
                'user_id' => $student_id,
                'score' => 5,
                'submission_count' => 1,
                'answered_correctly_at_least_once' => false,
                'submission' => 'some submission']);
            $data = [
                'type' => 'q',
                'user_id' => $student_id,
                'assignment_id' => $this->assignment->id,
                'question_id' => $this->question->id,
                'submission' => 'fake_1.pdf',
                'original_filename' => 'orig_fake_1.pdf',
                'date_submitted' => Carbon::now()];
            SubmissionFile::create($data);
        }
    }

    /** @test */

    public function owner_can_move_student_to_a_different_section()
    {

        $this->actingAs($this->user)->patchJson("/api/enrollments/{$this->course->id}}/{$this->student_user->id}",
            ['section_id' => $this->section_1->id])
            ->assertJson(['message' => "We have moved <strong>{$this->student_user->first_name} {$this->student_user->last_name}</strong> to <strong>{$this->section_1->name}</strong>."]);

    }


    /** @test */

    public function to_move_student_the_student_must_be_enrolled_in_owners_course()
    {
        $this->actingAs($this->user)->patchJson("/api/enrollments/{$this->course_2->id}}/{$this->student_user_2->id}")
            ->assertJson(['message' => 'You are not allowed to move this student.']);

    }


    /** @test */

    public function to_unenroll_student_must_be_enrolled_in_owners_course()
    {
        $this->actingAs($this->user)->deleteJson("/api/enrollments/{$this->section_2->id}}/{$this->student_user_2->id}")
            ->assertJson(['message' => 'You are not allowed to unenroll this student.']);

    }

    /** @test */

    public function owner_can_unenroll_student_from_course()
    {
        $this->actingAs($this->user)->deleteJson("/api/enrollments/{$this->section->id}}/{$this->student_user->id}")
            ->assertJson(['message' => "We have unenrolled <strong>{$this->student_user->first_name} {$this->student_user->last_name}</strong> from the course."]);

    }

    /** @test */

    public function correct_number_of_database_rows_remain_after_unenroll()
    {
        $enrollments = Enrollment::all()->count();
        $this->assertEquals(2, Extension::all()->count());
        $this->assertEquals(2, LtiGradePassback::all()->count());
        $this->assertEquals(2, AssignToUser::all()->count());
        $this->assertEquals(2, Submission::all()->count());
        $this->assertEquals(2, SubmissionFile::all()->count());
        $this->assertEquals(2, Score::all()->count());


        $this->actingAs($this->user)->deleteJson("/api/enrollments/{$this->section->id}}/{$this->student_user->id}")
            ->assertJson(['message' => "We have unenrolled <strong>{$this->student_user->first_name} {$this->student_user->last_name}</strong> from the course."]);
        $this->assertEquals(1, Extension::all()->count());
        $this->assertEquals(1, LtiGradePassback::all()->count());
        $this->assertEquals(1, AssignToUser::all()->count());
        $this->assertEquals(1, Submission::all()->count());
        $this->assertEquals(1, SubmissionFile::all()->count());
        $this->assertEquals(1, Score::all()->count());
        $this->assertEquals($enrollments - 1, Enrollment::all()->count());

    }


}
