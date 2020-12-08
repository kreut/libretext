<?php

namespace Tests\Feature\Instructors;

use Tests\TestCase;
use App\User;
use App\LearningTree;

class LearningTreesTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->learning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id]);
        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->learning_tree_info = ['page_id' => 10,
            'title' => 'some title',
            'description' => 'some_description',
            'library' => 'query',
            'text' => 'Query',
            'color' => 'green'];
    }


    /** @test */

    public function must_be_a_valid_remediation()
    {
        $this->learning_tree_info['page_id'] = 30;
        $this->learning_tree_info['library'] = 'chem';
        $this->actingAs($this->user)->postJson("api/learning-trees/info", $this->learning_tree_info)
        ->assertJsonValidationErrors(['message' => 'That does not look like a valid page id for the library.']);
    }



    /** @test */
    public function non_owner_cannot_delete_a_learning_tree()
    {
        $this->actingAs($this->user_2)->deleteJson("/api/learning-trees/{$this->learning_tree->id}")
            ->assertJson([
                'type' => 'error',
                'message' => 'You are not allowed to delete this Learning Tree.'
            ]);
    }

    /** @test */
    public function owner_can_delete_a_learning_tree()
    {
        $this->actingAs($this->user)->deleteJson("/api/learning-trees/{$this->learning_tree->id}")
            ->assertJson([
                'type' => 'info',
                'message' => 'The Learning Tree has been deleted.'
            ]);
    }

    /** @test */
    public function non_instructor_cannot_create_a_learning_tree()
    {

        $this->actingAs($this->student_user)->postJson("api/learning-trees/info", $this->learning_tree_info)
            ->assertJson([
                'message' => 'You are not allowed to save Learning Trees.',
            ]);
    }

    /** @test */
    public function instructor_can_create_a_learning_tree()
    {

        $this->actingAs($this->user)->postJson("api/learning-trees/info", $this->learning_tree_info)
            ->assertJson([
                'type' => 'success',
            ]);
    }

    /** @test */
    public function page_id_must_be_an_integer()
    {
        $this->learning_tree_info['page_id'] = -3;
        $this->actingAs($this->user)->postJson("api/learning-trees/info", $this->learning_tree_info)
            ->assertJsonValidationErrors(['page_id']);
    }


    public function must_have_a_description()
    {

    }

    public function must_have_a_title()
    {

    }

    public function must_have_a_valid_library()
    {

    }

    public function non_instructor_cannot_save_a_tree_to_the_database(){



    }

    public function non_owner_cannot_update_a_tree_to_the_database(){



    }

    public function owner_can_update_a_tree_to_the_database(){



    }

}
