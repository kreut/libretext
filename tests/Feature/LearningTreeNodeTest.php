<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\LearningTree;
use App\Question;
use App\Section;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearningTreeNodeTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $learning_tree = <<<heredoc
{"html":"<div class='blockelem noselect block selectedblock question-border' style='left: 330px; top: 109.797px;'>        <input type='hidden' name='blockelemtype' class='blockelemtype' value='2'>        <input type='hidden' name='question_id' value='10'>          <input type='hidden' name='blockid' class='blockid' value='0'><span class='blockyname' style='margin-bottom:0;'><span class='question_id'>10</span></span><div class='blockyinfo'>Comparativos y superlativos actividad 3: drag text</div></div><div class='blockelem noselect block selectedblock exposition-border' style='left: 332px; top: 225.797px;'>        <input type='hidden' name='blockelemtype' class='blockelemtype' value='2'>        <input type='hidden' name='question_id' value='102438'>          <input type='hidden' name='blockid' class='blockid' value='1'><span class='blockyname' style='margin-bottom:0;'><span class='question_id'>102438</span></span><div class='blockyinfo'>Exposition 1</div></div><div class='arrowblock' style='left: 433px; top: 195.797px;'><input type='hidden' class='arrowid' value='1'><svg preserveAspectRatio='none' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M20 0L20 15L20 15L20 30' stroke='#C5CCD0' stroke-width='2px'></path><path d='M15 25H25L20 30L15 25Z' fill='#C5CCD0'></path></svg></div><div class='blockelem noselect block selectedblock exposition-border' style='left: 201px; top: 339.797px;'>        <input type='hidden' name='blockelemtype' class='blockelemtype' value='2'>        <input type='hidden' name='question_id' value='102439'>          <input type='hidden' name='blockid' class='blockid' value='2'><span class='blockyname' style='margin-bottom:0;'><span class='question_id'>102439</span></span><div class='blockyinfo'>Exposition 2</div><div class='indicator invisible' style='left: 116px; top: 84px;'></div></div><div class='arrowblock' style='left: 317px; top: 309.797px;'><input type='hidden' class='arrowid' value='2'><svg preserveAspectRatio='none' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M136 0L136 15L5 15L5 30' stroke='#C5CCD0' stroke-width='2px'></path><path d='M0 25H10L5 30L0 25Z' fill='#C5CCD0'></path></svg></div><div class='blockelem noselect block selectedblock exposition-border' style='left: 463px; top: 339.797px;'>        <input type='hidden' name='blockelemtype' class='blockelemtype' value='2'>        <input type='hidden' name='question_id' value='102441'>          <input type='hidden' name='blockid' class='blockid' value='4'><span class='blockyname' style='margin-bottom:0;'><span class='question_id'>102441</span></span><div class='blockyinfo'>Exposition 3</div></div><div class='arrowblock' style='left: 433px; top: 309.797px;'><input type='hidden' class='arrowid' value='4'><svg preserveAspectRatio='none' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M20 0L20 15L151 15L151 30' stroke='#C5CCD0' stroke-width='2px'></path><path d='M146 25H156L151 30L146 25Z' fill='#C5CCD0'></path></svg></div><div class='blockelem noselect block selectedblock question-border' style='left: 463px; top: 455.797px;'>        <input type='hidden' name='blockelemtype' class='blockelemtype' value='2'>        <input type='hidden' name='question_id' value='102443'>          <input type='hidden' name='blockid' class='blockid' value='5'><span class='blockyname' style='margin-bottom:0;'><span class='question_id'>102443</span></span><div class='blockyinfo'>37885</div></div><div class='arrowblock' style='left: 564px; top: 423.797px;'><input type='hidden' class='arrowid' value='5'><svg preserveAspectRatio='none' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M20 0L20 15L20 15L20 32' stroke='#C5CCD0' stroke-width='2px'></path><path d='M15 27H25L20 32L15 27Z' fill='#C5CCD0'></path></svg></div><div class='blockelem noselect block selectedblock question-border' style='left: 201px; top: 453.797px;'>        <input type='hidden' name='blockelemtype' class='blockelemtype' value='2'>        <input type='hidden' name='question_id' value='202'>          <input type='hidden' name='blockid' class='blockid' value='3'><span class='blockyname' style='margin-bottom:0;'><span class='question_id'>202</span></span><div class='blockyinfo'>webwork question</div></div><div class='arrowblock' style='left: 302px; top: 423.797px;'><input type='hidden' class='arrowid' value='3'><svg preserveAspectRatio='none' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M20 0L20 15L20 15L20 30' stroke='#C5CCD0' stroke-width='2px'></path><path d='M15 25H25L20 30L15 25Z' fill='#C5CCD0'></path></svg></div>","blockarr":[{"childwidth":504,"parent":-1,"id":0,"x":792,"y":151.796875,"width":242,"height":84},{"childwidth":504,"parent":0,"id":1,"x":793,"y":266.796875,"width":242,"height":84},{"childwidth":242,"parent":1,"id":2,"x":662,"y":380.796875,"width":242,"height":84},{"childwidth":242,"parent":1,"id":4,"x":924,"y":380.796875,"width":242,"height":84},{"childwidth":0,"parent":4,"id":5,"x":924,"y":496.796875,"width":242,"height":84},{"childwidth":0,"parent":2,"id":3,"x":662,"y":494.796875,"width":242,"height":84}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"2"},{"name":"question_id","value":"10"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block selectedblock question-border"},{"style":"left: 330px; top: 109.797px;"}]},{"id":1,"parent":0,"data":[{"name":"blockelemtype","value":"2"},{"name":"question_id","value":"102438"},{"name":"blockid","value":"1"}],"attr":[{"class":"blockelem noselect block selectedblock exposition-border"},{"style":"left: 332px; top: 225.797px;"}]},{"id":2,"parent":1,"data":[{"name":"blockelemtype","value":"2"},{"name":"question_id","value":"102439"},{"name":"blockid","value":"2"}],"attr":[{"class":"blockelem noselect block selectedblock exposition-border"},{"style":"left: 201px; top: 339.797px;"}]},{"id":4,"parent":1,"data":[{"name":"blockelemtype","value":"2"},{"name":"question_id","value":"102441"},{"name":"blockid","value":"4"}],"attr":[{"class":"blockelem noselect block selectedblock exposition-border"},{"style":"left: 463px; top: 339.797px;"}]},{"id":5,"parent":4,"data":[{"name":"blockelemtype","value":"2"},{"name":"question_id","value":"102443"},{"name":"blockid","value":"5"}],"attr":[{"class":"blockelem noselect block selectedblock question-border"},{"style":"left: 463px; top: 455.797px;"}]},{"id":3,"parent":2,"data":[{"name":"blockelemtype","value":"2"},{"name":"question_id","value":"202"},{"name":"blockid","value":"3"}],"attr":[{"class":"blockelem noselect block selectedblock question-border"},{"style":"left: 201px; top: 453.797px;"}]}]}
heredoc;

        $this->learning_tree = factory(LearningTree::class)->create([
            'user_id' => $this->user->id,
            'learning_tree' => $learning_tree]);
        $final_node_question_id = $this->learning_tree->finalQuestionIds()[0];
        $this->node_question_id = $final_node_question_id;
        $this->node_question = factory(Question::class)->create(['id' => $this->node_question_id, 'technology' => 'text']);
        $this->root_node_question = factory(Question::class)->create(['id' => $this->learning_tree->root_node_question_id, 'technology' => 'text']);
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->root_node_question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => '0'
        ]);
    }

    /** @test */
    public function gets_the_correct_time_left_for_exposition_node_node_or_text_question()
    {
        $this->assignment->min_number_of_minutes_in_exposition_node = 15;
        $this->assignment->save();
        $response = $this->actingAs($this->student_user)->getJson("/api/learning-tree-node-assignment-question/assignment/{$this->assignment->id}/learning-tree/{$this->learning_tree->id}/question/$this->node_question_id")
            ->content();
        $this->assertEquals(15*60*1000,json_decode($response)->node_question->time_left);
    }

    /** @test */
    public function only_valid_student_can_get_credit_for_completion()
    {
        $this->actingAs($this->user)
            ->postJson("/api/learning-tree-node-assignment-question/assignment/{$this->assignment->id}/learning-tree/{$this->learning_tree->id}/question/$this->node_question_id/give-credit-for-completion")
            ->assertJson(['message' => 'You are not a student in this course.']);

    }

    /** @test */
    public function must_be_text_based_or_exposition_to_get_timed_credit()
    {

        $this->node_question->technology = 'webwork';
        $this->node_question->save();
        $this->actingAs($this->student_user)->postJson("/api/learning-tree-node-assignment-question/assignment/{$this->assignment->id}/learning-tree/{$this->learning_tree->id}/question/{$this->node_question_id}/give-credit-for-completion")
            ->assertJson(['message' => 'The question should either be text-based or an exposition question.']);
    }


    public function can_only_reset_root_node_submission_question_is_in_your_assignment()
    {
        $this->actingAs($this->user)->getJson("/api/learning-tree-node/reset-root-node-submission/assignment/{$this->assignment->id}/question/{$this->node_question->id}")
            ->assertJson(['message' => 'This is not a formative assignment.']);
    }

    public function can_only_reset_root_node_submission_question_if_question_is_not_past_due()
    {
        $this->actingAs($this->user)->getJson("/api/learning-tree-node/reset-root-node-submission/assignment/{$this->assignment->id}/question/{$this->node_question->id}")
            ->assertJson(['message' => 'This is not a formative assignment.']);
    }

    public function only_owner_of_learning_tree_node_submission_can_view_it()
    {
        $this->actingAs($this->user)->getJson("api/learning-tree-node-submission/{$this->learning_tree_node_submission->id}")
            ->assertJson(['message' => 'This is not a formative assignment.']);
    }

    public function correctly_applies_reset()
    {
        $this->actingAs($this->user)->getJson("api/learning-tree-node-submission/{$this->learning_tree_node_submission->id}")
            ->assertJson(['message' => 'This is not a formative assignment.']);
    }

}
