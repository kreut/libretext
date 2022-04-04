<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\LearningTreeHistory;
use App\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use App\User;
use App\LearningTree;

class LearningTreesIndexTest extends TestCase
{


    private $learning_tree;
    private $user;
    /**
     * @var Collection|Model|mixed
     */
    private $student_user;
    /**
     * @var Collection|Model|mixed
     */
    private $assignment;
    /**
     * @var Collection|Model|mixed
     */
    private $user_2;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $course->id]);
        $this->user_2 = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->learning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id]);

        factory(Question::class)->create(['page_id' => $this->learning_tree->root_node_page_id,
        'library' => $this->learning_tree->root_node_library]);

    }

    /** @test */
    public function non_owner_cannot_create_learning_tree_from_template()
    {
        $this->actingAs($this->student_user)->postJson("api/learning-trees/{$this->learning_tree->id}/create-learning-tree-from-template")
            ->assertJson(['message' => 'You are not allowed to create a template from this Learning Tree.']);


    }

    /** @test */
    public function owner_can_create_learning_tree_from_template()
    {
        $num_learning_tree_histories = count(LearningTreeHistory::all());
        $this->actingAs($this->user)->postJson("api/learning-trees/{$this->learning_tree->id}/create-learning-tree-from-template")
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('learning_trees', ['title' => $this->learning_tree->title . ' copy']);
        $num_learning_tree_histories_with_copy = count(LearningTreeHistory::all());
        $this->assertEquals($num_learning_tree_histories+1,$num_learning_tree_histories_with_copy );


    }

    /** @test */

    public function non_instructor_cannot_import_learning_trees()
    {

        $this->actingAs($this->student_user)->postJson("/api/learning-trees/import", ['learning_tree_ids' => $this->learning_tree->id])
            ->assertJson(['message' => 'You are not allowed to import Learning Trees.']);

    }

    /** @test */
    public function imported_learning_trees_must_be_valid()
    {
        $this->actingAs($this->user)->postJson("/api/learning-trees/import", ['learning_tree_ids' => "{$this->learning_tree->id},badValue,3"])
            ->assertJsonValidationErrors(['learning_tree_ids']);


    }

    /** @test */

    public function instructor_can_import_learning_trees()
    {

        $this->actingAs($this->user)->postJson("/api/learning-trees/import", ['learning_tree_ids' => $this->learning_tree->id])
            ->assertJson(['type' => 'success']);


    }

    /** @test */
    public function existing_learning_tree_can_be_retrieved()
    {

        $learning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id, 'learning_tree' =>'{"html":"some html","blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"274162"},{"name":"library","value":"chem"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 191, 255); left: 486px; top: 144.797px;"}]},{"id":6,"parent":0,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"278275"},{"name":"library","value":"chem"},{"name":"blockid","value":"6"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 191, 255); left: 204px; top: 311.797px;"}]}]}']);
        $this->actingAs($this->user)->postJson("/api/learning-trees/learning-tree-exists", ['learning_tree_id' => $learning_tree->id])
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function user_is_told_if_learning_tree_does_not_exist()
    {

        $this->actingAs($this->user)->postJson("/api/learning-trees/learning-tree-exists", ['learning_tree_id' => 0])
            ->assertJson(['message' => 'We were not able to locate that learning tree.']);

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

        $this->assignment['learning_tree_success_level'] = 'tree';
        $this->assignment['learning_tree_success_criteria'] = 'time based';
        $this->assignment['number_of_successful_branches_for_a_reset'] = 1;
        $this->assignment['free_pass_for_satisfying_learning_tree_criteria'] = 1;
     $this->assignment->save();
        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/learning-trees/{$this->learning_tree->id}")
            ->assertJson([
                'message' => 'The Learning Tree has been added to the assignment.'
            ]);

    }


}
