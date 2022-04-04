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
            'root_node_library' => 'query',
            'root_node_page_id' => 102685
        ]);
        $this->flowy = <<<EOT
{"html":"<div class="blockelem noselect block" style="border: 1px solid rgb(0, 96, 188); left: 327px; top: 145.797px;">\n        <input type="hidden" name="blockelemtype" class="blockelemtype" value="2">\n        <input type="hidden" name="page_id" value="1867">\n        <input type="hidden" name="library" value="query">\n\n      \n    <input type="hidden" name="blockid" class="blockid" value="0"><div class="blockyleft">\n<p class="blockyname" style="margin-bottom:0;"> <img src="/assets/img/query.svg" alt="query" style="#0060bc"><span class="library">Query</span> - <span class="page_id">1867</span> \n<span class="extra"></span></p></div><p></p>\n<div class="blockydiv"></div>\n<div class="blockyinfo">Comparativos y superlativos a...\n</div></div><div class="indicator invisible" style="left: 116px; top: 116px;"></div>","blockarr":[{"childwidth":242,"parent":-1,"id":0,"x":789,"y":203.296875,"width":242,"height":115}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"2"},{"name":"page_id","value":"1867"},{"name":"library","value":"query"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"border: 1px solid rgb(0, 96, 188); left: 327px; top: 145.797px;"}]}]}
EOT;
        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->learning_tree_info = ['page_id' => 102685,
            'is_root_node' => true,
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
            'order' => 1,
            'points' => 10
        ]);
    }

    /** @test */
    public function root_node_must_be_auto_graded_when_using_assignment_question_id()
    {
        $this->question->save();
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
    public function non_root_node_can_be_either_when_using_assignment_question_id()
    {

        $assignment_question_id = "{$this->assignment->id}-{$this->question->id}";
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-assignment-question-id/$assignment_question_id/0")
            ->assertJson(['type' => "success",
            ]);

        $this->question->technology = 'text';
        $this->question->save();
        $assignment_question_id = "{$this->assignment->id}-{$this->question->id}";
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-assignment-question-id/$assignment_question_id/0")
            ->assertJson(['type' => "success"]);

    }


    /** @test */
    public function non_root_node_can_be_either_when_using_library_page_id()
    {
        $this->question->save();
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-library-page-id/{$this->question->library}/{$this->question->page_id}/0")
            ->assertJson(['type' => "success",
            ]);

        $this->question->technology = 'text';
        $this->question->save();
        $this->actingAs($this->user)->getJson("/api/learning-trees/validate-remediation-by-library-page-id/{$this->question->library}/{$this->question->page_id}/0")
            ->assertJson(['type' => "success"]);

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
    public function owner_can_update_a_tree_to_the_database()
    {
        $this->learning_tree_info['learning_tree'] = $this->flowy;
        $this->learning_tree_info['branch_description'] = 'some new description';
        $this->actingAs($this->user)->patchJson("/api/learning-trees/{$this->learning_tree->id}", $this->learning_tree_info)
            ->assertJson([
                'type' => 'no_change'
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
