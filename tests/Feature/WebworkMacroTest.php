<?php

namespace Tests\Feature;

use App\User;
use App\WebworkMacro;
use App\WebworkMacroEditor;
use App\WebworkMacroRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use function factory;

class WebworkMacroTest extends TestCase
{
    private $admin;
    private $editor;
    private $student;
    private $macro;

    private $macro_payload;

    public function setUp(): void
    {
        parent::setUp();

        // Admin: email must be in admin_emails table (or use me@me.com in testing env)
        $this->admin = factory(User::class)->create(['email' => 'me@me.com']);

        // Editor: a regular user who exists in webwork_macro_editors
        $this->editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id' => $this->editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Student: role 3, not in webwork_macro_editors
        $this->student = factory(User::class)->create(['role' => 3]);

        // A macro owned by the editor
        $this->macro = WebworkMacro::create([
            'user_id' => $this->editor->id,
            'name' => 'testMacro.pl',
            'description' => 'A test macro',
            'macro' => 'sub test { return 1; }',
            'is_retired' => false,
        ]);

        $this->macro_payload = [
            'name' => 'newMacro.pl',
            'description' => 'A new macro',
            'macro' => 'sub newMacro { return 2; }',
        ];
    }

    // -------------------------------------------------------------------------
    // INDEX
    // -------------------------------------------------------------------------

    /** @test */
    public function any_authenticated_user_can_list_macros(): void
    {
        $this->actingAs($this->student)
            ->getJson('/api/webwork-macros')
            ->assertJson(['type' => 'success'])
            ->assertJsonFragment(['name' => 'testMacro.pl']);
    }

    /** @test */
    public function unauthenticated_user_cannot_list_macros(): void
    {
        $this->getJson('/api/webwork-macros')
            ->assertUnauthorized();
    }

    /** @test */
    public function retired_macros_are_excluded_from_index(): void
    {
        $this->macro->is_retired = true;
        $this->macro->save();

        $this->actingAs($this->student)
            ->getJson('/api/webwork-macros')
            ->assertJson(['type' => 'success'])
            ->assertJsonMissing(['name' => 'testMacro.pl']);
    }

    /** @test */
    public function admin_index_response_includes_creators_list(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/webwork-macros')
            ->assertJson(['type' => 'success'])
            ->assertJsonStructure(['creators']);
    }

    /** @test */
    public function non_admin_index_response_does_not_include_creators_list(): void
    {
        $response = $this->actingAs($this->student)
            ->getJson('/api/webwork-macros')
            ->assertJson(['type' => 'success'])
            ->json();

        $this->assertArrayNotHasKey('creators', $response);
    }

    /** @test */
    public function admin_can_filter_macros_by_user_id(): void
    {
        // Create a second editor with their own macro
        $other_editor = factory(User::class)->create();
        WebworkMacro::create([
            'user_id' => $other_editor->id,
            'name' => 'otherMacro.pl',
            'description' => 'Other macro',
            'macro' => 'sub other { return 3; }',
            'is_retired' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/webwork-macros?user_id={$this->editor->id}")
            ->assertJson(['type' => 'success'])
            ->json();

        $names = collect($response['webwork_macros'])->pluck('name')->toArray();
        $this->assertContains('testMacro.pl', $names);
        $this->assertNotContains('otherMacro.pl', $names);
    }

    /** @test */
    public function index_flags_which_macros_are_in_a_given_question(): void
    {
        $question = factory(\App\Question::class)->create(['page_id' => 23482671]);
        $question_revision_id = 1;

        DB::table('question_webwork_macros')->insert([
            'question_id'          => $question->id,
            'question_revision_id' => $question_revision_id,
            'webwork_macro_id'     => $this->macro->id,
        ]);

        $response = $this->actingAs($this->editor)
            ->getJson("/api/webwork-macros?question_id={$question->id}&question_revision_id={$question_revision_id}")
            ->assertJson(['type' => 'success'])
            ->json();

        $macro_in_response = collect($response['webwork_macros'])
            ->firstWhere('id', $this->macro->id);

        $this->assertTrue((bool) $macro_in_response['in_question']);
    }

    // -------------------------------------------------------------------------
    // STORE
    // -------------------------------------------------------------------------

    /** @test */
    public function editor_can_create_a_macro(): void
    {
        Http::fake([
            '*/api/macros*' => Http::response([], 200),
            '*/api/authored/macros*' => Http::response([], 200),
        ]);

        $this->actingAs($this->editor)
            ->postJson('/api/webwork-macros', $this->macro_payload)
            ->assertJson([
                'type' => 'success',
                'message' => 'The macro has been created.',
            ]);

        $this->assertDatabaseHas('webwork_macros', [
            'name' => 'newMacro.pl',
            'user_id' => $this->editor->id,
        ]);
    }

    /** @test */
    public function admin_can_create_a_macro(): void
    {
        // Admin must also be in webwork_macro_editors for the store gate
        DB::table('webwork_macro_editors')->insertOrIgnore([
            'user_id'            => $this->admin->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        Http::fake([
            '*/api/macros*' => Http::response([], 200),
            '*/api/authored/macros*' => Http::response([], 200),
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/webwork-macros', $this->macro_payload)
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function student_cannot_create_a_macro(): void
    {
        $this->actingAs($this->student)
            ->postJson('/api/webwork-macros', $this->macro_payload)
            ->assertJson(['message' => 'You are not allowed to create macros.']);
    }

    /** @test */
    public function store_is_blocked_when_macro_already_exists_on_webwork_server(): void
    {
        Http::fake([
            '*/api/macros*' => Http::response([['name' => 'newMacro.pl']], 200),
        ]);

        $this->actingAs($this->editor)
            ->postJson('/api/webwork-macros', $this->macro_payload)
            ->assertJson([
                'type' => 'error',
                'message' => 'A macro named newMacro.pl already exists on the WeBWork server.',
            ]);
    }

    /** @test */
    public function store_returns_error_when_webwork_server_post_fails(): void
    {
        Http::fake([
            '*/api/macros*' => Http::response([], 200),
            '*/api/authored/macros*' => Http::response([], 500),
        ]);

        $this->actingAs($this->editor)
            ->postJson('/api/webwork-macros', $this->macro_payload)
            ->assertJson([
                'type' => 'error',
                'message' => 'We were not able to save the macro to the WeBWork server. Please try again or contact us for assistance.',
            ]);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    /** @test */
    public function editor_can_update_their_own_macro(): void
    {
        Http::fake([
            '*/api/authored/macros*' => Http::response([], 200),
        ]);

        $this->actingAs($this->editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name' => 'updatedMacro.pl',
                'description' => 'Updated description',
                'macro' => 'sub updated { return 99; }',
                'reason_for_edit' => 'Fixed a bug',
            ])
            ->assertJson([
                'type' => 'success',
                'message' => 'The macro has been updated.',
            ]);

        $this->assertDatabaseHas('webwork_macros', [
            'id' => $this->macro->id,
            'name' => 'updatedMacro.pl',
        ]);
    }

    /** @test */
    public function admin_can_update_any_macro(): void
    {
        DB::table('webwork_macro_editors')->insertOrIgnore([
            'user_id'            => $this->admin->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        Http::fake([
            '*/api/authored/macros*' => Http::response([], 200),
        ]);

        $this->actingAs($this->admin)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name'           => 'adminUpdated.pl',
                'description'    => 'Admin updated',
                'macro'          => 'sub adminUpdated { return 0; }',
                'reason_for_edit'=> 'Admin fix',
            ])
            ->assertJson(['type' => 'success']);

        $this->assertDatabaseHas('webwork_macros', [
            'id'   => $this->macro->id,
            'name' => 'adminUpdated.pl',
        ]);
    }

    /** @test */
    public function editor_cannot_update_another_editors_macro(): void
    {
        $other_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id' => $other_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($other_editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name' => 'hacked.pl',
                'description' => 'Hacked',
                'macro' => 'sub hacked {}',
                'reason_for_edit' => 'some reason'
            ])
            ->assertJson(['message' => 'You are not allowed to edit that macro.']);
    }

    /** @test */
    public function student_cannot_update_a_macro(): void
    {
        $this->actingAs($this->student)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name' => 'hacked.pl',
                'description' => 'Hacked',
                'macro' => 'sub hacked {}',
                'reason_for_edit' => 'some reason'
            ])
            ->assertJson(['message' => 'You are not allowed to edit that macro.']);
    }

    /** @test */
    public function update_returns_error_when_webwork_server_post_fails(): void
    {
        Http::fake([
            '*/api/authored/macros*' => Http::response([], 500),
        ]);

        $this->actingAs($this->editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name' => 'updatedMacro.pl',
                'description' => 'Updated',
                'macro' => 'sub updated {}',
                'reason_for_edit' => 'Fix',
            ])
            ->assertJson([
                'type' => 'error',
                'message' => 'We were not able to save the macro to the WeBWork server. Please try again or contact us for assistance.',
            ]);
    }

    // -------------------------------------------------------------------------
    // DESTROY
    // -------------------------------------------------------------------------

    /** @test */
    public function editor_can_retire_their_own_macro(): void
    {
        $this->actingAs($this->editor)
            ->deleteJson("/api/webwork-macros/{$this->macro->id}")
            ->assertJson([
                'type' => 'info',
                'message' => "The macro {$this->macro->name} has been retired and will no longer appear in the list.",
            ]);

        $this->assertDatabaseHas('webwork_macros', [
            'id' => $this->macro->id,
            'is_retired' => true,
        ]);
    }

    /** @test */
    public function admin_can_retire_any_macro(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson("/api/webwork-macros/{$this->macro->id}")
            ->assertJson(['type' => 'info']);

        $this->assertDatabaseHas('webwork_macros', [
            'id' => $this->macro->id,
            'is_retired' => true,
        ]);
    }

    /** @test */
    public function editor_cannot_retire_another_editors_macro(): void
    {
        $other_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id' => $other_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($other_editor)
            ->deleteJson("/api/webwork-macros/{$this->macro->id}")
            ->assertJson(['message' => 'You are not allowed to retire that macro.']);
    }

    /** @test */
    public function student_cannot_retire_a_macro(): void
    {
        $this->actingAs($this->student)
            ->deleteJson("/api/webwork-macros/{$this->macro->id}")
            ->assertJson(['message' => 'You are not allowed to retire that macro.']);
    }

    /** @test */
    public function macro_in_use_by_a_question_cannot_be_retired(): void
    {
        $question = factory(\App\Question::class)->create(['page_id' => 23482671]);

        DB::table('question_webwork_macros')->insert([
            'question_id'          => $question->id,
            'question_revision_id' => 1,
            'webwork_macro_id'     => $this->macro->id,
        ]);

        $this->actingAs($this->editor)
            ->deleteJson("/api/webwork-macros/{$this->macro->id}")
            ->assertJson(['type' => 'error']);

        $this->assertDatabaseHas('webwork_macros', [
            'id'         => $this->macro->id,
            'is_retired' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // REVISIONS
    // -------------------------------------------------------------------------

    /** @test */
    public function editor_can_view_revisions_for_a_macro(): void
    {
        WebworkMacroRevision::create([
            'webwork_macro_id' => $this->macro->id,
            'name' => $this->macro->name,
            'description' => $this->macro->description,
            'macro' => $this->macro->macro,
            'edited_by_user_id' => $this->editor->id,
            'revision_number' => 0,
            'reason_for_edit' => null,
        ]);

        $this->actingAs($this->editor)
            ->getJson("/api/webwork-macros/{$this->macro->id}/revisions")
            ->assertJson(['type' => 'success'])
            ->assertJsonStructure(['revisions']);
    }

    /** @test */
    public function student_cannot_view_revisions(): void
    {
        $this->actingAs($this->student)
            ->getJson("/api/webwork-macros/{$this->macro->id}/revisions")
            ->assertJson(['message' => 'You are not allowed to view the revisions.']);
    }
}
