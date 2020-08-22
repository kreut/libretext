<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StudentsAssignmentsIndexTest extends TestCase
{


    /** @test */
   public function can_get_assignment_file_info_if_owner() {

   }
    /** @test */
    public function cannot_get_assignment_file_info_if_owner() {

    }

    public function can_download_assignment_file_if_owner() {
        //tested in instructors/AssignmentFileTest
    }

    public function can_get_temporary_url_if_owner() {
        //tested in instructors/AssignmentFileTest
    }

    public function can_store_assignment_file_if_owner() {

    }

    public function cannot_store_assignment_file_if_not_ower(){

}

}
