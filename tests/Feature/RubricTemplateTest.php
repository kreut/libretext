<?php

namespace Tests\Feature;

use App\RubricTemplate;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RubricTemplateTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->rubric_items = [['criterion' => 'blah', 'points' => 6]];
        $this->rubricTemplate = new RubricTemplate();
        $this->rubricTemplate->user_id = $this->user->id;
        $this->rubricTemplate->name = 'blah';
        $this->rubricTemplate->description = 'blah';
        $this->rubricTemplate->rubric = json_encode($this->rubric_items);
        $this->rubricTemplate->save();
    }

    /** @test */
    public function non_instructor_cannot_store_rubric_template()
    {
        $template_info = [
            'name' => 'some name',
            'description' => 'some description',
            'save_as_template' => true,
            'rubric_items' => $this->rubric_items
        ];
        $student_user = factory(User::class)->create(['role' => 3]);

        $this->actingAs($student_user)
            ->postJson("/api/rubric-templates", $template_info)
            ->assertJson(['message' => 'You are not allowed to create a rubric template.']);

    }

    /** @test */
    public function non_owner_cannot_update_rubric_template()
    {
        $template_info = [
            'name' => 'some names',
            'description' => 'some description',
            'rubric_items' => $this->rubric_items
        ];

        $this->actingAs($this->user_2)
            ->patchJson("/api/rubric-templates/{$this->rubricTemplate->id}", $template_info)
            ->assertJson(['message' => 'You are not allowed to update this rubric template.']);

    }

    /** @test */
    public function non_owner_cannot_delete_rubric_template()
    {

        $this->actingAs($this->user_2)
            ->deleteJson("/api/rubric-templates/{$this->rubricTemplate->id}")
            ->assertJson(['message' => 'You are not allowed to delete this rubric template.']);

    }

    /** @test */
    public function non_owner_cannot_copy_rubric_template()
    {

        $this->actingAs($this->user_2)
            ->patchJson("/api/rubric-templates/{$this->rubricTemplate->id}/copy")
            ->assertJson(['message' => 'You are not allowed to copy this rubric template.']);

    }

}
