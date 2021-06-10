<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\LearningTreeHistory;
use App\Question;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\User;
use App\LearningTree;

class LearningTreesEditorTest extends TestCase
{

    private $user_2;
    /**
     * @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private $learning_tree;
    /**
     * @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private $learning_tree_history;
    /**
     * @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private $student_user;
    /**
     * @var array
     */
    private $learning_tree_info;
    /**
     * @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private $user;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->learning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id]);
        $this->learning_tree_history = factory(LearningTreeHistory::class)->create([
            'learning_tree_id' => $this->learning_tree->id,
            'learning_tree' => $this->learning_tree->learning_tree,
            'root_node_library' => 'query',
            'root_node_page_id' => 102685
        ]);
        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->learning_tree_info = ['page_id' => 102685,
            'title' => 'some title',
            'description' => 'some_description',
            'library' => 'query',
            'text' => 'Query',
            'color' => 'green'];
        $this->question = factory(Question::class)->create(['page_id' => 102685]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'open_ended_submission_type' => 'none',
            'order'=> 1,
            'points' => 10
        ]);
    }

    /** @test */
    public function owner_cannot_update_a_node_if_in_assignment()
    {
        $assignment_question_id = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->select('id')
            ->first()
            ->id;

        DB::table('assignment_question_learning_tree')->insert([
            'assignment_question_id' => $assignment_question_id,
            'learning_tree_id' => $this->learning_tree->id
        ]);
        $this->learning_tree_info['learning_tree'] = '{"key":"value"}';
        $this->actingAs($this->user)->patchJson("/api/learning-trees/nodes/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson([
                'message' => "It looks like you're using this Learning Tree in {$this->course->name} --- {$this->assignment->name}.  Please first remove that question from the assignment before attempting to update the node.",
            ]);

    }

    /** @test */

    public function must_be_a_valid_node()
    {
        $this->learning_tree_info['page_id'] = 30000000000;
        $this->learning_tree_info['library'] = 'chem';
        $this->actingAs($this->user)->postJson("api/learning-trees/info", $this->learning_tree_info)
            ->assertJson(['message' => 'We were not able to validate this Learning Tree node.  Please double check your library and page id or contact us for assistance.']);
    }

    /** @test */

    public function non_owner_cannot_undo_learning_tree()
    {
        $this->actingAs($this->user_2)->patchJson("/api/learning-tree-histories/{$this->learning_tree->id}")
            ->assertJson(['message' => 'You are not allowed to update this Learning Tree.']);

    }

    /** @test */

    public function owner_can_undo_learning_tree()
    {
        $this->actingAs($this->user)->patchJson("/api/learning-tree-histories/{$this->learning_tree->id}")
            ->assertJson(['type' => 'info']);

    }

    /** @test */
    public function non_owner_cannot_update_a_node()
    {
        $this->learning_tree_info['learning_tree'] = '{"key":"value"}';
        $this->actingAs($this->user_2)->patchJson("/api/learning-trees/nodes/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson([
                'message' => 'You are not allowed to update this node.',
            ]);

    }

    /** @test */
    public function owner_can_update_a_node()
    {
        $this->learning_tree_info['learning_tree'] = '{"key":"value"}';
        $this->actingAs($this->user)->patchJson("/api/learning-trees/nodes/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson([
                'type' => 'success',
            ]);

    }



    /** @test */
    public function owner_can_update_a_tree_to_the_database()
    {
        $this->learning_tree_info['learning_tree'] = '{"key":"value"}';
        $this->actingAs($this->user)->patchJson("/api/learning-trees/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson([
                'type' => 'success'
            ]);

    }

    /** @test */
    public function non_owner_cannot_update_a_tree_to_the_database()
    {
        $this->learning_tree_info['learning_tree'] = '{"key":"value"}';
        $this->actingAs($this->user_2)->patchJson("/api/learning-trees/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson([
                'message' => 'You are not allowed to update this Learning Tree.',
            ]);

    }




    /** @test */
    public function must_have_a_description()
    {
        $this->learning_tree_info['description'] = '';
        $this->actingAs($this->user)->postJson("api/learning-trees/info", $this->learning_tree_info)
            ->assertJsonValidationErrors(['description']);
    }

    /** @test */
    public function must_have_a_title()
    {
        $this->learning_tree_info['title'] = '';
        $this->actingAs($this->user)->postJson("api/learning-trees/info", $this->learning_tree_info)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function must_have_a_valid_library()
    {
        $this->learning_tree_info['library'] = 'does not exist';
        $this->actingAs($this->user)->postJson("api/learning-trees/info", $this->learning_tree_info)
            ->assertJsonValidationErrors(['library']);
    }

    /** @test */
    public function non_owner_cannot_save_a_tree_to_the_database()
    {

        $this->actingAs($this->student_user)->patchJson("/api/learning-trees/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson([
                'message' => 'You are not allowed to update this Learning Tree.',
            ]);
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


}
