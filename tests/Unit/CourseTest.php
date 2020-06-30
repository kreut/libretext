<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CourseTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function it_gets_user_courses_in_descending_order() {

    }
    public function it_allows_the_owner_of_a_course_to_delete_it() {

    }

    public function it_allows_the_owner_of_a_course_to_update_it() {

    }

    public function it_allows_the_owner_of_a_course_to_create_it() {

    }

    public function it_allows_the_user_to_udpate_a_course_without_changing_the_name(){


    }

    public function the_course_end_date_must_be_past_the_course_start_date() {

    }

    public function the_course_start_and_end_dates_must_be_valid() {

    }

    public function the_name_of_the_course_must_not_be_empty() {

    }
}
