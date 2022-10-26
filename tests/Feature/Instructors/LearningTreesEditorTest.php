<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\LearningTreeHistory;
use App\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\User;
use App\LearningTree;

class LearningTreesEditorTest extends TestCase
{

    private $user_2;
    /**
     * @var Collection|Model|mixed
     */
    private $learning_tree;
    /**
     * @var Collection|Model|mixed
     */
    private $student_user;
    /**
     * @var array
     */
    private $learning_tree_info;
    /**
     * @var Collection|Model|mixed
     */
    private $user;
    /**
     * @var Collection|Model|mixed
     */
    private $question;
    /**
     * @var Collection|Model|mixed
     */
    private $assignment;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->learning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id]);
        $this->learning_tree_history = factory(LearningTreeHistory::class)->create([
            'learning_tree_id' => $this->learning_tree->id,
            'learning_tree' => $this->learning_tree->learning_tree,
            'root_node_question_id' => 102685
        ]);
        $this->flowy = <<<EOT
{"html":"<div class="blockelem noselect block empty-node-border" style="left: 318px; top: 77.7969px;">\n        <input type="hidden" name="blockelemtype" class="blockelemtype" value="2">\n        <input type="hidden" name="question_id" value="145715">\n\n\n      \n    <input type="hidden" name="blockid" class="blockid" value="0">\n<span class="blockyname" style="margin-bottom:0;"> <span class="question_id">145715</span> \n<span class="extra"></span></span>\n<div class="blockyinfo">Empty Learning Tree Node\n</div><div class="indicator invisible" style="left: 116px; top: 82px;"></div></div><div class="blockelem noselect block exposition-border" style="left: 318px; top: 189.797px;">\n        <input type="hidden" name="blockelemtype" class="blockelemtype" value="2">\n        <input type="hidden" name="question_id" value="3">\n\n\n      \n    <input type="hidden" name="blockid" class="blockid" value="1">\n<span class="blockyname" style="margin-bottom:0;"> <span class="question_id">3</span> \n<span class="extra"></span></span>\n<div class="blockyinfo">7.1 Actividad # 1: Cláusulas con 'si' en situaciones reales\n</div></div><div class="arrowblock" style="left: 419px; top: 159.797px;"><input type="hidden" class="arrowid" value="1"><svg preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 0L20 15L20 15L20 29" stroke="#C5CCD0" stroke-width="2px"></path><path d="M15 24H25L20 29L15 24Z" fill="#C5CCD0"></path></svg></div>","blockarr":[{"childwidth":242,"parent":-1,"id":0,"x":780,"y":118.796875,"width":242,"height":82},{"childwidth":0,"parent":0,"id":1,"x":779,"y":229.796875,"width":242,"height":82}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"2"},{"name":"question_id","value":"145715"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block empty-node-border"},{"style":"left: 318px; top: 77.7969px;"}]},{"id":1,"parent":0,"data":[{"name":"blockelemtype","value":"2"},{"name":"question_id","value":"3"},{"name":"blockid","value":"1"}],"attr":[{"class":"blockelem noselect block exposition-border"},{"style":"left: 318px; top: 189.797px;"}]}]}
EOT;
        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->question = factory(Question::class)->create(['id' => 145715]);
        $this->question_2 = factory(Question::class)->create(['id' => 3]);
        $this->learning_tree_info = ['question_id' => $this->question->id,
            'is_root_node' => true,
            'title' => 'some title',
            'description' => 'some_description',
            'public' => 1,
            'text' => 'Query',
            'color' => 'green'];

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 10
        ]);
    }

    /** @test */
    public function instructors_can_get_question_types()
    {
        $this->question->question_type = 'exposition';
        $this->question->save();
        $question_types['question_types'][$this->question->id] = 'exposition';
        $this->actingAs($this->user)->postJson("api/questions/question-types", ['question_ids' => [$this->question->id]])
            ->assertJson($question_types);
    }

    /** @test */
    public function non_instructor_cannot_get_question_types()
    {
        $this->actingAs($this->student_user)->postJson("api/questions/question-types", ['question_ids' => [1, 2, 3]])
            ->assertJson(['message' => 'You are not allowed to get the question types.']);

    }

    /** @test */
    public function owner_can_update_a_tree_to_the_database()
    {
        $this->learning_tree_info['learning_tree'] = $this->flowy;
        $this->learning_tree_info['branch_description'] = 'some new description';
        $this->learning_tree_info['question_ids'] = [3, 145715];
        $this->actingAs($this->user)->patchJson("/api/learning-trees/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson([
                'type' => 'no_change'
            ]);

    }

    /** @test */
    public function non_root_node_can_be_auto_graded_when_using_question_id()
    {
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-assignment-question-id/{$this->question->id}/0")
            ->assertJson(['type' => "success",
            ]);

    }

    /** @test */
    public function non_root_node_can_be_text_when_using_question_id()
    {

        $this->question->technology = 'text';
        $this->question->save();
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-assignment-question-id/{$this->question->id}/0")
            ->assertJson(['type' => "success"]);

    }

    /** @test */
    public function non_root_node_can_be_auto_graded_when_using_assignment_question_id()
    {

        $assignment_question_id = "{$this->assignment->id}-{$this->question->id}";
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-assignment-question-id/$assignment_question_id/0")
            ->assertJson(['type' => "success",
            ]);

    }

    /** @test */
    public function non_root_node_can_be_text_when_using_assignment_question_id()
    {


        $this->question->technology = 'text';
        $this->question->save();
        $assignment_question_id = "{$this->assignment->id}-{$this->question->id}";
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-assignment-question-id/$assignment_question_id/0")
            ->assertJson(['type' => "success"]);

    }

    /** @test */
    public function non_instructor_cannot_get_all_learning_trees()
    {

        $this->user->role = 3;
        $this->user->save();

        $this->actingAs($this->user)->postJson("/api/learning-trees/all")
            ->assertJson(['message' => "You are not allowed to get all Learning Trees."]);
    }

    /** @test */
    public function root_node_must_be_auto_graded_when_using_assignment_question_id()
    {

        $assignment_question_id = "{$this->assignment->id}-{$this->question->id}";
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-assignment-question-id/$assignment_question_id/1")
            ->assertJson(['type' => "success",
            ]);

        $this->question->technology = 'text';
        $this->question->save();
        $assignment_question_id = "{$this->assignment->id}-{$this->question->id}";
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-assignment-question-id/$assignment_question_id/1")
            ->assertJson(['message' => "The root node in the assessment should have an auto-graded technology.",
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
            'learning_tree_id' => $this->learning_tree->id,
            'learning_tree_success_level' => 'tree',
            'learning_tree_success_criteria' => 'time based',
            'number_of_successful_branches_for_a_reset' => 1,
            'free_pass_for_satisfying_learning_tree_criteria' => 1
        ]);
        $this->learning_tree_info['learning_tree'] = '{"key":"value"}';
        $this->actingAs($this->user)->patchJson("/api/learning-trees/nodes/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson([
                'message' => "It looks like you're using this Learning Tree in {$this->course->name} --- {$this->assignment->name}.  Please first remove that question from the assignment before attempting to update the node.",
            ]);

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

        $this->learning_tree_info['learning_tree'] = $this->flowy;


        $this->learning_tree_info['is_root_node'] = true;
        $this->actingAs($this->user)->patchJson("/api/learning-trees/nodes/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function branch_description_is_required_for_non_root_node()
    {
        $this->learning_tree_info['learning_tree'] = '{"key":"value"}';
        $this->learning_tree_info['branch_description'] = '';
        $this->learning_tree_info['is_root_node'] = false;
        $this->actingAs($this->user)
            ->patchJson("/api/learning-trees/nodes/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJsonValidationErrors('branch_description');

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


}
