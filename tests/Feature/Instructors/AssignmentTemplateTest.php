<?php

namespace Tests\Feature\Instructors;

use App\AssignmentTemplate;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssignmentTemplateTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->assignment_template_info = [
            'template_name' => 'Some Template',
            'template_description' => 'Some Description',
            'user_id' => $this->user->id,
            'can_view_hint' => 0,
            'scoring_type' => 'p',
            'source' => 'a',
            'points_per_question' => 'number of points',
            'default_points_per_question' => 2,
            'students_can_view_assignment_statistics' => 0,
            'include_in_weighted_average' => 1,
            'late_policy' => 'not accepted',
            'assessment_type' => 'delayed',
            'default_open_ended_submission_type' => 'file',
            'instructions' => 'Some instructions',
            "number_of_randomized_assessments" => null,
            'notifications' => 1,
            'algorithmic' => 0,
            'assignment_group_id' => 1,
            'file_upload_mode' => 'both',
            'is_template' => 1,
            'assign_to_everyone' => 0
        ];

        $this->assignment_template = factory(AssignmentTemplate::class)->create([
            'user_id' => $this->user->id,
            'template_name' => 'first template']);
        $this->assignment_template_2 = factory(AssignmentTemplate::class)->create([
            'user_id' => $this->user->id,
            'template_name' => 'second template',
            'order' => 2]);

    }

    /** @test */
    public
    function non_owner_cannot_reorder_assignment_templates()
    {
        $this->actingAs($this->student_user)->patchJson("/api/assignment-templates/order", [
            'ordered_assignment_templates' => [$this->assignment_template_2->id, $this->assignment_template->id]
        ])->assertJson(['message' => 'You are not allowed to re-order an assignment template that is not yours.']);
    }

    /** @test */
    public
    function owner_can_reorder_assignment_templates()
    {
        //dd($this->assignment->order . ' ' . $this->assignment_2->order);
        $this->actingAs($this->user)->patchJson("/api/assignment-templates/order", [
            'ordered_assignment_templates' => [$this->assignment_template_2->id, $this->assignment_template->id]
        ]);
        $assignment_templates = DB::table('assignment_templates')
            ->where('user_id', $this->user->id)
            ->get()
            ->sortBy('order')
            ->pluck('id')
            ->toArray();
        $this->assertEquals([$this->assignment_template_2->id, $this->assignment_template->id], $assignment_templates);

    }

    /** @test * */
    public function instructor_can_store_assignment_templates()
    {
        $this->actingAs($this->user)->postJson("/api/assignment-templates", $this->assignment_template_info)
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('assignment_templates', ['user_id' => $this->user->id]);

    }

    /** @test * */
    public function template_names_must_be_unique()
    {
        $this->actingAs($this->user)->postJson("/api/assignment-templates", $this->assignment_template_info)
            ->assertJson(['type' => 'success']);
        $this->actingAs($this->user)->postJson("/api/assignment-templates", $this->assignment_template_info)
            ->assertJsonValidationErrors('template_name');

    }

    /** @test * */
    public function instructor_can_update_assignment_templates()
    {
        $this->assignment_template_info['template_name'] = "new name";
        $this->actingAs($this->user)->postJson("/api/assignment-templates", $this->assignment_template_info)
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('assignment_templates', ['user_id' => $this->user->id, 'template_name' => 'new name']);

    }

    /** @test * */
    public function non_instructor_cannot_get_assignment_templates()
    {

        $this->actingAs($this->student_user)->getJson("/api/assignment-templates")
            ->assertJson(['message' => 'You are not allowed to get the assignment templates.']);

    }

    /** @test * */
    public function instructor_can_get_assignment_templates()
    {
        $this->actingAs($this->user)->getJson("/api/assignment-templates")
            ->assertJson(['type' => 'success']);

    }

    /** @test * */
    public function non_instructor_cannot_store_assignment_templates()
    {
        $this->actingAs($this->student_user)->postJson("/api/assignment-templates", $this->assignment_template_info)
            ->assertJson(['message' => 'You are not allowed to save assignment templates.']);

    }

    /** @test * */
    public function template_name_must_be_valid()
    {
        $this->assignment_template_info['template_name'] = '';
        $this->actingAs($this->user)->postJson("/api/assignment-templates", $this->assignment_template_info)
            ->assertJsonValidationErrors('template_name');

    }

    /** @test * */
    public function template_description_must_be_valid()
    {
        $this->assignment_template_info['template_description'] = '';
        $this->actingAs($this->user)->postJson("/api/assignment-templates", $this->assignment_template_info)
            ->assertJsonValidationErrors('template_description');

    }

    /** @test */
    public function non_owner_cannot_retrieve_assignment_template()
    {
        $this->actingAs($this->student_user)->getJson("/api/assignment-templates/{$this->assignment_template->id}")
            ->assertJson(['message' => 'You are not allowed to retrieve this assignment template.']);
    }

    /** @test */
    public function owner_can_retrieve_assignment_template()
    {

        $this->actingAs($this->user)->getJson("/api/assignment-templates/{$this->assignment_template->id}")
            ->assertJson(['type' => 'info']);
    }

    /** @test */
    public function non_owner_cannot_delete_assignment_template()
    {


        $this->actingAs($this->student_user)->deleteJson("/api/assignment-templates/{$this->assignment_template->id}")
            ->assertJson(['message' => 'You are not allowed to delete this assignment template.']);

    }

    /** @test */
    public function owner_can_delete_assignment_template()
    {
        $current_count = AssignmentTemplate::count();
        $this->actingAs($this->user)->deleteJson("/api/assignment-templates/{$this->assignment_template->id}")
            ->assertJson(['type' => 'info']);
        $this->assertDatabaseCount('assignment_templates', $current_count - 1);


    }

    /** @test */
    public function non_owner_cannot_copy_assignment_template()
    {
        $this->actingAs($this->student_user)->patchJson("/api/assignment-templates/copy/{$this->assignment_template->id}")
            ->assertJson(['message' => 'You are not allowed to copy this assignment template.']);


    }

    /** @test */
    public function owner_can_copy_assignment_template()
    {
        $this->actingAs($this->user)->patchJson("/api/assignment-templates/copy/{$this->assignment_template->id}")
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('assignment_templates', ['template_name' => "{$this->assignment_template->template_name} copy"]);

    }


}
