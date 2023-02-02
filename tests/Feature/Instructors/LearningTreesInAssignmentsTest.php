<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Cutup;
use App\Enrollment;
use App\LearningTree;
use App\Question;
use App\Section;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearningTreesInAssignmentsTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();

        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;


        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);


        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id,
            'solutions_released' => 0,
            'assessment_type' => 'learning tree']);
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student_user->id);
        $this->question = factory(Question::class)->create(['page_id' => 728162]);


        $this->assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 10
        ]);

        $this->learning_tree_rubric = [
            'number_of_successful_paths_for_a_reset' => 1,
            'number_of_resets' => 1];
    }



}
