<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use App\Question;

class RemediationsTest extends TestCase
{

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create(['role' => 3]);
        $this->question = factory(Question::class)->create();

    }


    /** @test */
  public function gets_own_learning_tree_if_exists(){
      $this->actingAs($this->user)->postJson("/api/learning-trees",[
          'question_id' => $this->question->id,
          'learning_tree' => 'some learning tree'
      ]);

      $this->actingAs($this->user)->getJson("/api/learning-trees/{$this->question->id}")
      ->assertJson(['learning_tree' => ['some learning tree']]);

  }

    /** @test */
    public function gets_other_owner_learning_tree_if_owner_has_no_learning_tree(){
        $this->actingAs($this->user)->postJson("/api/learning-trees",[
            'question_id' => $this->question->id,
            'learning_tree' => 'some learning tree'
        ]);

        $this->actingAs($this->user_2)->getJson("/api/learning-trees/{$this->question->id}")
            ->assertJson(['learning_tree' => ['some learning tree']]);

    }

    public function cannot_get_learning_tree_if_owner(){


    }
/** @test */
    public function can_save_learning_tree_if_owner(){

        $this->actingAs($this->user)->postJson("/api/learning-trees",[
            'question_id' => $this->question->id,
            'learning_tree' => 'some learning tree'
        ])->assertJson(['type' => 'success']);
    }

    /** @test */
    public function can_update_learning_tree_if_owner(){

        $this->actingAs($this->user)->postJson("/api/learning-trees",[
            'question_id' => $this->question->id,
            'learning_tree' => 'some learning tree'
        ])->assertJson(['type' => 'success']);

        $this->actingAs($this->user)->postJson("/api/learning-trees",[
            'question_id' => $this->question->id,
            'learning_tree' => 'some other learning tree'
        ])->assertJson(['type' => 'success']);
    }


    /** @test */
    public function cannot_save_learning_tree_if_not_instructor()
    {
        $this->actingAs($this->user_2)->postJson("/api/learning-trees", [
            'question_id' => $this->question->id,
            'learning_tree' => 'some learning tree'
        ])->assertJson(['type' => 'error',
            'message' => 'You are not allowed to save Learning Trees.']);
    }

/** @test */
    public function can_get_student_learning_objectives(){
        $this->actingAs($this->user)->getJson("/api/libreverse/library/chem/page/21691/student-learning-objectives")
            ->assertSeeText('To recognize the breadth, depth, and scope of chemistry.');


    }
}
