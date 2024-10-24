<?php

namespace Tests\Feature;

use App\Course;
use App\Section;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InviteStudentsTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->student_from_roster_invitation = ['email' => 'some@email.com',
            'first_name' => 'Some first name',
            'last_name' => 'Some last name',
            'invitation_type' => 'student_from_roster_invitation',
            'course_id' => $this->course->id,
            'section_id' => $this->section->id];
    }

    /** @test */
    /*public function student_can_enroll_with_access_code_from_pending_invitations()
    {
        $this->student_user->email = 'me@blah.com';
        $this->student_user->save();
        DB::table('pending_course_invitations')->insert([
            'email' => 'me@blah.com',
            'course_id' => $this->course->id,
            'section_id' => $this->section->id,
            'last_name' => '',
            'first_name' => '',
            'student_id' => '',
            'access_code' => 'some-silly-code',
            'status' => 'pending'
        ]);
        DB::table('whitelisted_domains')
            ->insert([
                'whitelisted_domain' => 'blah.com',
                'course_id' => $this->course->id
            ]);
        $this->assertDatabaseHas('pending_course_invitations', ['access_code' => 'some-silly-code']);
        $this->actingAs($this->student_user)
            ->postJson("/api/enrollments", [
                'access_code' => 'some-silly-code'])
            ->assertJson(['message' => "You are now enrolled in <strong>{$this->course->name} - {$this->section->name}</strong>."]);
        $this->assertDatabaseCount('pending_course_invitations', 0);
    }*/

    /** @test */
    public function non_owner_cannot_revoke_all_invitations()
    {
        $this->actingAs($this->user_2)
            ->deleteJson("/api/users/courses/{$this->course->id}/revoke-student-invitations")
            ->assertJson(['message' => 'You are not allowed to revoke student invitations for this course.']);

    }

    /** @test */
    public function non_course_owner_cannot_invite_student()
    {
        $this->actingAs($this->user_2)
            ->postJson("/api/users/invite-student", $this->student_from_roster_invitation)
            ->assertJson(['message' => 'You are not allowed to send student invitations to this course.']);

    }

    /** @test */
    public function non_course_owner_cannot_revoke_single_invitation()
    {
        $pending_course_invitation = DB::table('pending_course_invitations')->insertGetId([
            'email' => 'some-email',
            'course_id' => $this->course->id,
            'section_id' => $this->section->id,
            'last_name' => '',
            'first_name' => '',
            'student_id' => '',
            'access_code' => 'code',
            'status' => 'pending'
        ]);
        $this->actingAs($this->user_2)
            ->deleteJson("/api/pending-course-invitations/$pending_course_invitation")
            ->assertJson(['message' => 'You are not allowed to delete this pending course invitation.']);

    }

    /** @test */
    public function non_instructor_cannot_get_s3_presigned_url()
    {

        $this->actingAs($this->student_user)
            ->postJson("/api/s3/pre-signed-url", ['upload_file_type' => 'student-roster',
                'file_name' => 'student-roster-template.csv'])
            ->assertJson(['message' => 'You are not allowed to upload upload a student roster.']);

    }

    /** @test */
    public function non_instructor_cannot_get_student_roster_template()
    {

        $this->actingAs($this->student_user)
            ->postJson("/api/users/student-roster-upload-template")
            ->assertJson(['message' => 'You are not allowed to get the student roster upload template.']);

    }

    /** @test */
    public function non_instructor_cannot_get_pending_course_invitations()
    {
        $this->actingAs($this->user_2)
            ->getJson("/api/pending-course-invitations/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to get the pending course invitations for this course.']);

    }


}
