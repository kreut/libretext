<?php

namespace Tests\Feature\Nursing;

use App\PatientInformation;
use App\User;
use App\Course;
use App\Assignment;
use Tests\TestCase;

class PatientInformationTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->patient_information = new PatientInformation();

    }

    /** @test */
    public function non_owner_cannot_update_showing_updated_patient_information()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/patient-information/show-patient-updated-information/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to update whether to show the updated Patient Information.']);
    }

    /** @test */
    public function non_owner_cannot_get_patient_information()
    {
        $this->actingAs($this->user_2)
            ->getJson("/api/patient-information/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to view this patient information.']);
    }


    /** @test */
    public function patient_information_must_be_valid()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/patient-information/{$this->assignment->id}", ['code_status' => 'bogus', 'weight_units' => 'bad'])
            ->assertJsonValidationErrors(['name', 'gender', 'allergies', 'age', 'weight', 'dob', 'code_status']);

    }


}
