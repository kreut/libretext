<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Section;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MathPixTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->student_user->save();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id, 'solutions_released' => 0]);

    }

    /** @test */
    public function non_instructor_cannot_get_temporary_url()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/math-pix/temporary-url", ['user_id' => $this->student_user->id, 'assignment_id' => $this->assignment->id, 'question_id' => 1])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to get a temporary URL for this SMILE.']);

    }
    /** @test */
    public function non_student_cannot_convert_to_smiles()
    {
        $this->actingAs($this->student_user)
            ->putJson("/api/math-pix/convert-to-smiles", ['user_id' => $this->student_user->id, 'assignment_id' => $this->assignment->id, 'question_id' => 1])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to convert this to SMILES.']);

    }

}
