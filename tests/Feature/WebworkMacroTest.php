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

        $this->admin = factory(User::class)->create(['email' => 'me@me.com']);

        $this->editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $this->editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->student = factory(User::class)->create(['role' => 3]);

        $macro_id = DB::table('webwork_macros')->insertGetId([
            'user_id'     => $this->editor->id,
            'name'        => 'testMacro.pl',
            'description' => 'A test macro',
            'macro'       => 'sub test { return 1; }',
            'is_retired'  => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        $this->macro = WebworkMacro::find($macro_id);

        $this->macro_payload = [
            'name'        => 'newMacro.pl',
            'description' => 'A new macro',
            'macro'       => 'sub newMacro { return 2; }',
        ];
    }

    // -------------------------------------------------------------------------
    // INDEX
    // -------------------------------------------------------------------------

    /** @test */
    public function unauthenticated_user_cannot_list_macros(): void
    {
        $this->getJson('/api/webwork-macros')
            ->assertUnauthorized();
    }

    /** @test */
    public function index_returns_co_editor_count_for_each_macro(): void
    {
        $co_editor = factory(User::class)->create();
        DB::table('webwork_macro_co_editors')->insert([
            'webwork_macro_id' => $this->macro->id,
            'user_id'          => $co_editor->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        Http::fake([
            '*/api/macros*' => Http::response([[
                'name'        => 'testMacro.pl',
                'source_type' => 'custom',
                'created_at'  => now(),
            ]], 200),
        ]);

        $response = $this->actingAs($this->editor)
            ->getJson('/api/webwork-macros')
            ->assertJson(['type' => 'success'])
            ->json();

        $macro_entry = collect($response['webwork_macros'])
            ->firstWhere('name', 'testMacro.pl');

        $this->assertEquals(1, $macro_entry['co_editor_count']);
    }

    /** @test */
    public function global_editor_sees_all_macros_including_ones_they_did_not_create(): void
    {
        // A second macro owned by a different editor
        DB::table('webwork_macros')->insertGetId([
            'user_id'     => $this->admin->id,
            'name'        => 'otherMacro.pl',
            'description' => 'Another macro',
            'macro'       => 'sub other { return 0; }',
            'is_retired'  => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        Http::fake([
            '*/api/macros*' => Http::response([
                ['name' => 'testMacro.pl', 'source_type' => 'custom', 'created_at' => now()],
                ['name' => 'otherMacro.pl', 'source_type' => 'custom', 'created_at' => now()],
            ], 200),
        ]);

        $response = $this->actingAs($this->editor)
            ->getJson('/api/webwork-macros')
            ->assertJson(['type' => 'success'])
            ->json();

        $names = collect($response['webwork_macros'])->pluck('name');
        $this->assertContains('testMacro.pl', $names);
        $this->assertContains('otherMacro.pl', $names);
    }

    /** @test */
    public function global_editor_can_create_new_macros(): void
    {
        Http::fake([
            '*/api/macros*'          => Http::response([[
                'name'        => 'testMacro.pl',
                'source_type' => 'custom',
                'created_at'  => now(),
            ]], 200),
        ]);

        $response = $this->actingAs($this->editor)
            ->getJson('/api/webwork-macros')
            ->assertJson(['type' => 'success'])
            ->json();

        $this->assertTrue($response['can_create']);
    }

    // -------------------------------------------------------------------------
    // STORE
    // -------------------------------------------------------------------------

    /** @test */
    public function editor_can_create_a_macro(): void
    {
        Http::fake([
            '*/api/macros*'         => Http::response([], 200),
            '*/api/authored/macros*' => Http::response([], 200),
        ]);

        $this->actingAs($this->editor)
            ->postJson('/api/webwork-macros', $this->macro_payload)
            ->assertJson([
                'type'    => 'success',
                'message' => 'The macro has been created.',
            ]);

        $this->assertDatabaseHas('webwork_macros', [
            'name'    => 'newMacro.pl',
            'user_id' => $this->editor->id,
        ]);
    }

    /** @test */
    public function admin_can_create_a_macro(): void
    {
        DB::table('webwork_macro_editors')->insertOrIgnore([
            'user_id'            => $this->admin->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        Http::fake([
            '*/api/macros*'         => Http::response([], 200),
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
    public function store_returns_error_when_webwork_server_post_fails(): void
    {
        Http::fake([
            '*/api/macros*'         => Http::response([], 200),
            '*/api/authored/macros*' => Http::response([], 500),
        ]);

        $this->actingAs($this->editor)
            ->postJson('/api/webwork-macros', $this->macro_payload)
            ->assertJson([
                'type'    => 'error',
                'message' => 'Error saving macro: WeBWork server returned status 500.',
            ]);
    }

    /** @test */
    public function store_creates_revision_0_attributed_to_creator(): void
    {
        Http::fake([
            '*/api/macros*'         => Http::response([], 200),
            '*/api/authored/macros*' => Http::response([], 200),
        ]);

        $this->actingAs($this->editor)
            ->postJson('/api/webwork-macros', $this->macro_payload)
            ->assertJson(['type' => 'success']);

        $macro = WebworkMacro::where('name', 'newMacro.pl')->first();

        $this->assertDatabaseHas('webwork_macro_revisions', [
            'webwork_macro_id'  => $macro->id,
            'revision_number'   => 0,
            'edited_by_user_id' => $this->editor->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    /** @test */
    public function editor_can_update_their_own_macro(): void
    {
        Http::fake(['*/api/authored/macros*' => Http::response([], 200)]);

        $this->actingAs($this->editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name'           => 'updatedMacro.pl',
                'description'    => 'Updated description',
                'macro'          => 'sub updated { return 99; }',
                'reason_for_edit'=> 'Fixed a bug',
            ])
            ->assertJson([
                'type'    => 'success',
                'message' => 'The macro has been updated.',
            ]);

        $this->assertDatabaseHas('webwork_macros', [
            'id'   => $this->macro->id,
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

        Http::fake(['*/api/authored/macros*' => Http::response([], 200)]);

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
            'user_id'            => $other_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->actingAs($other_editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name'           => 'hacked.pl',
                'description'    => 'Hacked',
                'macro'          => 'sub hacked {}',
                'reason_for_edit'=> 'some reason',
            ])
            ->assertJson(['message' => 'You are not allowed to edit that macro.']);
    }

    /** @test */
    public function student_cannot_update_a_macro(): void
    {
        $this->actingAs($this->student)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name'           => 'hacked.pl',
                'description'    => 'Hacked',
                'macro'          => 'sub hacked {}',
                'reason_for_edit'=> 'some reason',
            ])
            ->assertJson(['message' => 'You are not allowed to edit that macro.']);
    }

    /** @test */
    public function update_returns_error_when_webwork_server_post_fails(): void
    {
        Http::fake(['*/api/authored/macros*' => Http::response([], 500)]);

        $this->actingAs($this->editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name'           => 'updatedMacro.pl',
                'description'    => 'Updated',
                'macro'          => 'sub updated {}',
                'reason_for_edit'=> 'Fix',
            ])
            ->assertJson([
                'type'    => 'error',
                'message' => 'Error saving macro: WeBWork server returned status 500.',
            ]);
    }

    /** @test */
    public function update_records_revision_attributed_to_actual_editor_not_owner(): void
    {
        // Set up a co-editor who is different from the macro owner
        $co_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $co_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        DB::table('webwork_macro_co_editors')->insert([
            'webwork_macro_id' => $this->macro->id,
            'user_id'          => $co_editor->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        Http::fake(['*/api/authored/macros*' => Http::response([], 200)]);

        $this->actingAs($co_editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name'           => $this->macro->name,
                'description'    => $this->macro->description,
                'macro'          => 'sub updated { return 5; }',
                'reason_for_edit'=> 'Co-editor fix',
            ])
            ->assertJson(['type' => 'success']);

        // The new revision must credit the co-editor, NOT the macro owner
        $this->assertDatabaseHas('webwork_macro_revisions', [
            'webwork_macro_id'  => $this->macro->id,
            'edited_by_user_id' => $co_editor->id,
            'reason_for_edit'   => 'Co-editor fix',
        ]);

        // The macro owner (user_id) must not have changed
        $this->assertDatabaseHas('webwork_macros', [
            'id'      => $this->macro->id,
            'user_id' => $this->editor->id,
        ]);
    }

    /** @test */
    public function lazy_revision_0_is_attributed_to_macro_owner_not_co_editor(): void
    {
        // No revisions exist yet for this macro
        $this->assertDatabaseMissing('webwork_macro_revisions', [
            'webwork_macro_id' => $this->macro->id,
        ]);

        $co_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $co_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        DB::table('webwork_macro_co_editors')->insert([
            'webwork_macro_id' => $this->macro->id,
            'user_id'          => $co_editor->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        Http::fake(['*/api/authored/macros*' => Http::response([], 200)]);

        $this->actingAs($co_editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name'           => $this->macro->name,
                'description'    => $this->macro->description,
                'macro'          => 'sub changed { return 3; }',
                'reason_for_edit'=> 'First edit by co-editor',
            ])
            ->assertJson(['type' => 'success']);

        // Revision 0 should be attributed to the original owner, not the co-editor
        $this->assertDatabaseHas('webwork_macro_revisions', [
            'webwork_macro_id'  => $this->macro->id,
            'revision_number'   => 0,
            'edited_by_user_id' => $this->editor->id, // owner
        ]);

        // The actual edit revision should credit the co-editor
        $this->assertDatabaseHas('webwork_macro_revisions', [
            'webwork_macro_id'  => $this->macro->id,
            'revision_number'   => 1,
            'edited_by_user_id' => $co_editor->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // CO-EDITORS
    // -------------------------------------------------------------------------

    /** @test */
    public function co_editor_can_update_an_assigned_macro(): void
    {
        $co_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $co_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        DB::table('webwork_macro_co_editors')->insert([
            'webwork_macro_id' => $this->macro->id,
            'user_id'          => $co_editor->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        Http::fake(['*/api/authored/macros*' => Http::response([], 200)]);

        $this->actingAs($co_editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name'           => $this->macro->name,
                'description'    => 'Co-editor description',
                'macro'          => 'sub coEdited { return 7; }',
                'reason_for_edit'=> 'Co-editor update',
            ])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function co_editor_cannot_update_a_macro_they_are_not_assigned_to(): void
    {
        $co_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $co_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        // Note: NOT inserted into webwork_macro_co_editors for $this->macro

        $this->actingAs($co_editor)
            ->patchJson("/api/webwork-macros/{$this->macro->id}", [
                'name'           => 'hacked.pl',
                'description'    => 'Hacked',
                'macro'          => 'sub hacked {}',
                'reason_for_edit'=> 'some reason',
            ])
            ->assertJson(['message' => 'You are not allowed to edit that macro.']);
    }

    /** @test */
    public function co_editor_cannot_retire_a_macro(): void
    {
        $co_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $co_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        DB::table('webwork_macro_co_editors')->insert([
            'webwork_macro_id' => $this->macro->id,
            'user_id'          => $co_editor->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->actingAs($co_editor)
            ->deleteJson("/api/webwork-macros/{$this->macro->id}")
            ->assertJson(['message' => 'You are not allowed to retire that macro.']);

        $this->assertDatabaseHas('webwork_macros', [
            'id'         => $this->macro->id,
            'is_retired' => false,
        ]);
    }

    /** @test */
    public function co_editor_can_view_revisions_for_assigned_macro(): void
    {
        $co_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $co_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        DB::table('webwork_macro_co_editors')->insert([
            'webwork_macro_id' => $this->macro->id,
            'user_id'          => $co_editor->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        WebworkMacroRevision::create([
            'webwork_macro_id'  => $this->macro->id,
            'name'              => $this->macro->name,
            'description'       => $this->macro->description,
            'macro'             => $this->macro->macro,
            'edited_by_user_id' => $this->editor->id,
            'revision_number'   => 0,
            'reason_for_edit'   => null,
        ]);

        $this->actingAs($co_editor)
            ->getJson("/api/webwork-macros/{$this->macro->id}/revisions")
            ->assertJson(['type' => 'success'])
            ->assertJsonStructure(['revisions']);
    }

    /** @test */
    public function co_editor_cannot_view_revisions_for_unassigned_macro(): void
    {
        $co_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $co_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        // NOT assigned as co-editor for $this->macro

        $this->actingAs($co_editor)
            ->getJson("/api/webwork-macros/{$this->macro->id}/revisions")
            ->assertJson(['message' => 'You are not authorised to view revisions for this macro.']);
    }

    /** @test */
    public function owner_can_add_a_co_editor(): void
    {
        $co_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $co_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->actingAs($this->editor)
            ->postJson("/api/webwork-macros/{$this->macro->id}/co-editors", [
                'user_id' => $co_editor->id,
            ])
            ->assertJson(['type' => 'success']);

        $this->assertDatabaseHas('webwork_macro_co_editors', [
            'webwork_macro_id' => $this->macro->id,
            'user_id'          => $co_editor->id,
        ]);
    }

    /** @test */
    public function non_owner_cannot_add_a_co_editor(): void
    {
        $other_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $other_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $new_user = factory(User::class)->create();

        $this->actingAs($other_editor)
            ->postJson("/api/webwork-macros/{$this->macro->id}/co-editors", [
                'user_id' => $new_user->id,
            ])
            ->assertJson(['message' => 'Only the macro owner or an admin can add co-editors.']);
    }

    /** @test */
    public function owner_can_remove_a_co_editor(): void
    {
        $co_editor = factory(User::class)->create();
        $co_editor_id = DB::table('webwork_macro_co_editors')->insertGetId([
            'webwork_macro_id' => $this->macro->id,
            'user_id'          => $co_editor->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->actingAs($this->editor)
            ->deleteJson("/api/webwork-macros/{$this->macro->id}/co-editors/{$co_editor_id}")
            ->assertJson(['type' => 'success']);

        $this->assertDatabaseMissing('webwork_macro_co_editors', [
            'id' => $co_editor_id,
        ]);
    }

    /** @test */
    public function non_owner_cannot_remove_a_co_editor(): void
    {
        $other_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $other_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $co_editor = factory(User::class)->create();
        $co_editor_id = DB::table('webwork_macro_co_editors')->insertGetId([
            'webwork_macro_id' => $this->macro->id,
            'user_id'          => $co_editor->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->actingAs($other_editor)
            ->deleteJson("/api/webwork-macros/{$this->macro->id}/co-editors/{$co_editor_id}")
            ->assertJson(['message' => 'Only the macro owner or an admin can remove co-editors.']);

        $this->assertDatabaseHas('webwork_macro_co_editors', [
            'id' => $co_editor_id,
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
                'type'    => 'info',
                'message' => "The macro {$this->macro->name} has been retired and will no longer appear in the list.",
            ]);

        $this->assertDatabaseHas('webwork_macros', [
            'id'         => $this->macro->id,
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
            'id'         => $this->macro->id,
            'is_retired' => true,
        ]);
    }

    /** @test */
    public function editor_cannot_retire_another_editors_macro(): void
    {
        $other_editor = factory(User::class)->create();
        DB::table('webwork_macro_editors')->insert([
            'user_id'            => $other_editor->id,
            'granted_by_user_id' => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
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
            'webwork_macro_id'  => $this->macro->id,
            'name'              => $this->macro->name,
            'description'       => $this->macro->description,
            'macro'             => $this->macro->macro,
            'edited_by_user_id' => $this->editor->id,
            'revision_number'   => 0,
            'reason_for_edit'   => null,
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
            ->assertJson(['message' => 'You are not authorised to view revisions for this macro.']);
    }
}
