<?php

namespace Tests\Feature\Students;

use App\Section;
use App\User;
use App\Course;
use App\Enrollment;
use App\WhitelistedDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StudentsCoursesIndexTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['email' => 'a' . $this->user->email]);//get it of the same domain
        $this->student_user->role = 3;
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $whitelistedDomain = new WhitelistedDomain();
        $whitelisted_domain = $whitelistedDomain->getWhitelistedDomainFromEmail($this->user->email);
        $this->whitelisted_domain = factory(WhitelistedDomain::class)->create(['course_id' => $this->course->id, 'whitelisted_domain' => $whitelisted_domain]);

        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->section_1 = factory(Section::class)->create(['course_id' => $this->course->id,
            'name' => 'Section 2',
            'access_code' => 'some_other_access_code']);

        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section_2 = factory(Section::class)->create(['course_id' => $this->course_2->id]);


    }

    /** @test */
     public function case_insensitive_email_works()
     {
         $whitelisted_domain = DB::table('whitelisted_domains')
             ->where('course_id', $this->course->id)
             ->select('whitelisted_domain')
             ->pluck('whitelisted_domain')
             ->first();
         DB::table('enrollments')->delete();
         DB::table('sections')->delete();
         $this->section = factory(Section::class)->create(['course_id' => $this->course->id, 'access_code' => 'sdeefsdfsdf']);

         $this->student_user->email = strtoupper($whitelisted_domain);
         $this->student_user->save();
         $this->actingAs($this->student_user)->postJson("/api/enrollments", [
             'section_id' => $this->section->id,
             'student_id' => 'some sort of id',
             'access_code' => $this->section->access_code
         ])->assertJson(['type' => 'success']);

     }

    /** @test */
    public function email_must_be_on_whitelisted_domain()
    {
        $whitelisted_domain = DB::table('whitelisted_domains')
            ->where('course_id', $this->course->id)
            ->select('whitelisted_domain')
            ->pluck('whitelisted_domain')
            ->first();
        DB::table('enrollments')->delete();
        DB::table('sections')->delete();
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id, 'access_code' => 'sdeefsdfsdf']);

        $this->student_user->email = 'badEmail@badEmail.com';
        $this->student_user->save();
        $this->actingAs($this->student_user)->postJson("/api/enrollments", [
            'section_id' => $this->section->id,
            'access_code' => $this->section->access_code
        ])->assertJson(['errors' => ['access_code' => ["You can only enroll in this course using an email from the following domain: $whitelisted_domain.  You are current trying to enroll using the email: {$this->student_user->email} which has a different domain. If you need to use this email, please contact your instructor so that they can add it to their list of whitelisted domains."]]]);

    }

    /** @test */
    public function same_last_name_and_student_id_cannot_enroll_in_the_same_course()
    {
        $enrollment = new Enrollment();
        $enrollment->user_id = $this->student_user->id;
        $enrollment->course_id = $this->course->id;
        $enrollment->section_id = $this->section->id;
        $enrollment->save();

        $this->student_user->student_id = '12345';
        $this->student_user->save();
        $this->student_user_2 = factory(User::class)->create([
            'last_name' => $this->student_user->last_name,
            'student_id' => $this->student_user->student_id,
            'email' => 'b' . $this->user->email, //ensure from the same domain
            'role' => 3]);

        $this->actingAs($this->student_user_2)->postJson("/api/enrollments", [
            'section_id' => $this->section_1->id,
            'student_id' => $this->student_user->student_id,
            'access_code' => $this->section_1->access_code
        ])->assertJson(['message' => 'Someone with your student ID and the same last name is already enrolled in this course.']);

    }


    /** @test */
    public function can_get_enrollments_of_shown_courses_if_user_is_a_student()
    {
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id,
            'section_id' => $this->section->id
        ]);

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course_2->id,
            'section_id' => $this->section_2->id
        ]);

        $this->course_2->shown = 0;
        $this->course_2->save();


        $response = $this->actingAs($this->student_user)->getJson("/api/enrollments");
        $this->assertEquals(1, count($response->original['enrollments']));

    }


    /** @test */
    public function can_get_enrollments_if_user_is_a_student()
    {


        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id,
            'section_id' => $this->section->id
        ]);


        $this->actingAs($this->student_user)->getJson("/api/enrollments")
            ->assertJson(['type' => 'success']);
    }


    /** @test */
    public function cannot_get_enrollments_if_user_is_not_a_student()
    {
        $this->actingAs($this->user)->getJson("/api/enrollments")
            ->assertJson(['type' => 'error',
                'message' => 'You must be a student to view your enrollments.']);
    }


    /** @test */
    public function can_enroll_in_a_course_with_a_valid_access_code()
    {

        $this->section = factory(Section::class)->create(['course_id' => $this->course->id, 'access_code' => 'sdeefsdfsdf']);
        $this->actingAs($this->student_user)->postJson("/api/enrollments", [
            'section_id' => $this->section->id,
            'student_id' => 'some id',
            'access_code' => $this->section->access_code
        ])->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_enroll_in_more_than_one_section_of_a_course()
    {
        $enrollment = new Enrollment();
        $enrollment->user_id = $this->student_user->id;
        $enrollment->course_id = $this->course->id;
        $enrollment->section_id = $this->section->id;
        $enrollment->save();

        $this->actingAs($this->student_user)->postJson("/api/enrollments", [
            'section_id' => $this->section_1->id,
            'student_id' => 'some-other-id',
            'access_code' => $this->section_1->access_code
        ])->assertJson(['message' => 'You are already enrolled in another section of this course.']);

    }


    /** @test */
    public function cannot_enroll_in_a_course_with_an_invalid_access_code()
    {
        $this->actingAs($this->student_user)->postJson("/api/enrollments", [
            'section_id' => $this->section->id,
            'access_code' => 'not the real code'
        ])->assertJsonValidationErrors(['access_code']);

    }

}
