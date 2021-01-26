<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use Tests\TestCase;
use App\User;
use App\LearningTree;

class LearningTreesGetTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->user_2 = factory(User::class)->create();
        $this->learning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id]);
        //create a student and enroll in the class

    }

    /** @test */
    public function existing_learning_tree_can_be_retrieved()
    {

        $this->actingAs($this->user)->postJson("/api/learning-trees/learning-tree-exists", ['learning_tree_id' => $this->learning_tree->id])
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function user_is_told_if_learning_tree_does_not_exist()
    {

        $this->actingAs($this->user)->postJson("/api/learning-trees/learning-tree-exists", ['learning_tree_id' => 0])
            ->assertJson(['message' => 'We were not able to locate that Learning Tree.']);

    }

    /** @test */
    public function non_owner_cannot_add_a_learning_tree_to_an_assignment()
    {

        $this->actingAs($this->user_2)->postJson("/api/assignments/{$this->assignment->id}/learning-trees/{$this->learning_tree->id}")
            ->assertJson([
                'message' => 'You are not allowed to add a question to this assignment.'
            ]);

    }

    /** @test */
    public function owner_can_add_a_valid_learning_tree_to_an_assignment()
    {

        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/learning-trees/{$this->learning_tree->id}")
            ->assertJson([
                'message' => 'The Learning Tree has been added to the assignment.'
            ]);

    }


}
