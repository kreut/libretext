<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StudentsAssignmentsTest extends TestCase
{

    /*Already tested getting the assignments in InstructorsAssignmentsTest*/
    /** @test */
    public function can_visit_students_assignments()
    {
        $response = $this->getJson('/students/assignments');
        $response->assertStatus(200);
    }


}
