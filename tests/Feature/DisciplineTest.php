<?php

namespace Tests\Feature;

use App\Discipline;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DisciplineTest extends TestCase
{
    public function setup(): void
    {
        parent::setup();
        $this->user = factory(User::class)->create(['id' => 93]);
        $this->discipline = Discipline::create(['name' => 'some discipline']);

    }

    /** @test */
    public function non_instructor_cannot_request_new_discipline()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->user)
            ->postJson("/api/disciplines/request-new", ['name' => 'some name'])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to request new disciplines.']);

    }


    /** @test */
    public function non_admin_cannot_save_disciplines()
    {
        $this->actingAs($this->user)
            ->postJson("/api/disciplines", ['name' => 'some name'])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to create a new discipline.']);

    }

    /** @test */
    public function non_admin_cannot_update_disciplines()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/disciplines/{$this->discipline->id}", ['name' => 'some name'])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to update a discipline.']);

    }

    /** @test */
    public function non_admin_cannot_delete_disciplines()
    {
        $this->actingAs($this->user)
            ->deleteJson("/api/disciplines/{$this->discipline->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to delete a discipline.']);


    }

    /** @test */
    public function disciplines_must_be_unique()
    {
        $this->user->id = 1;
        $this->user->save();
        $this->actingAs($this->user)
            ->postJson("/api/disciplines", ['name' => 'some discipline'])
            ->assertJsonValidationErrors('name');
    }


}
