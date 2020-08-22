<?php

namespace Tests\Feature\instructors;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AssignmentFileTest extends TestCase
{
    /** @test */

    public function can_get_assignment_files_if_owner() {

    }

   public function cannot_get_assignment_files_if_not_owner(){


   }

   public function can_download_assignment_file_if_owner() {
        //student or instructor check where this comes from....
   }

   public function can_get_temporary_url_from_request_if_owner() {
       //student or instructor
   }

   public function can_get_temporary_url_if_owner() {


   }

    public function cannot_get_temporary_url_if_not_owner() {


    }

    public function can_store_text_feedback_if_owner() {


    }

    public function cannot_store_text_feedback_if_not_owner() {


    }

    public function can_store_assignment_file_if_owner() {


    }

    public function cannot_store_assignment_file_if_notowner() {


    }
}
