<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\User;
use App\Extension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ScoresIndexTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        //enroll a student in that course
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);
    }
/** @test */
    public function can_update_assignment_score_if_owner(){
        $this->actingAs($this->user)->patchJson("/api/scores/{$this->assignment->id}/{$this->student_user->id}",
            [
                'score' => 3
            ])
            ->assertJson(['type' => 'success']);
    }
    /** @test */
    public function cannot_update_assignment_score_if_not_owner(){
        $this->actingAs($this->user_2)->patchJson("/api/scores/{$this->assignment->id}/{$this->student_user->id}",
            [
                'score' => 3
            ])
            ->assertJson([
                'type' => 'error',
                'message' => 'You are not allowed to update this score.']);
    }
/** @test */
    public function can_add_extension_if_owner(){
        $this->actingAs($this->user)->postJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2025-09-02',
                'extension_time' => '09:00:00'
            ])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function extension_date_cannot_be_in_the_past(){
        $this->actingAs($this->user)->postJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2019-09-02',
                'extension_time' => '09:00:00'
            ])
            ->assertJsonValidationErrors('extension_date');
    }

    /** @test */
    public function extension_time_must_be_a_time(){
        $this->actingAs($this->user)->postJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2029-09-02',
                'extension_time' => 'not a time'
            ])
            ->assertJsonValidationErrors('extension_time');
    }
/** @test */
    public function cannot_add_extension_if_not_owner(){
        $this->actingAs($this->user_2)->postJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2025-09-02',
                'extension_time' => '09:00:00'
            ])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to create an extension for this student/assignment.']);

    }
    public function creatExtensionForTesting(){
        //create an extension
        return factory(Extension::class)->create( [
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user->id
        ]);
    }
/** @test */
    public function can_update_extension_if_owner(){

        $this->creatExtensionForTesting();
        $this->actingAs($this->user)->patchJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2029-09-03',
                'extension_time' => '09:00:00'
            ])
            ->assertJson(['type' => 'success']);
    }
/** @test */
    public function cannot_update_extension_if_owner(){
        $this->creatExtensionForTesting();
        $this->actingAs($this->user_2)->patchJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2029-09-03',
                'extension_time' => '09:00:00'
            ])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to update this extension.'
            ]);
    }
/** @test */
    public function can_get_extension_if_owner(){
        $this->creatExtensionForTesting();
        $this->actingAs($this->user)->getJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}")
            ->assertJson(['type' => 'success']);
    }

/** @test */
    public function cannot_get_extension_for_student_if_not_owner(){
        $this->creatExtensionForTesting();
        $this->actingAs($this->user_2)->getJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to view this extension.']);

    }
/** @test */
    public function can_get_course_scores_if_owner(){
      $this->actingAs($this->user)->getJson("/api/scores/{$this->course->id}")
            ->assertJson(['hasAssignments' =>true]);//for the fake student
    }
    /** @test */
    public function cannot_get_course_scores_if_not_owner(){

            $this->actingAs($this->user_2)->getJson("/api/scores/{$this->course->id}")
                ->assertJson(['type' => 'error',
                    'message' => 'You are not allowed to view these scores.']);//for the fake student

    }


}
