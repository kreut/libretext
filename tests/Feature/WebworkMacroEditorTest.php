<?php

namespace Tests\Feature;

use App\User;
use App\WebworkMacroEditor;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use function factory;

class WebworkMacroEditorTest extends TestCase
{
    private $admin;
    private $editor;
    private $student;
    private $macro_editor;

    public function setUp(): void
    {
        parent::setUp();

        // Admin: me@me.com is treated as admin in testing environment
        $this->admin = factory(User::class)->create(['email' => 'me@me.com']);

        // An existing editor whose role can be managed
        $this->editor = factory(User::class)->create();
        $this->macro_editor = WebworkMacroEditor::create([
            'user_id' => $this->editor->id,
            'granted_by_user_id' => $this->admin->id,
        ]);

        // Student: role 3, not an editor
        $this->student = factory(User::class)->create(['role' => 3]);
    }

    /** @test */
    public function cannot_get_potential_users_if_you_have_an_incorrect_email()
    {
        $user = factory(User::class)->create();
        $response = $this->actingAs($user)
            ->get('/api/user/potential-webwork-editors');
        $this->assertEquals('You are not allowed to retrieve potential webwork editors from the database.', $response->original['message']);
    }



    /** @test */
    public function admin_can_list_macro_editors(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/webwork-macro-editors')
            ->assertJson(['type' => 'success'])
            ->assertJsonStructure(['editors'])
            ->assertJsonFragment(['user_id' => $this->editor->id]);
    }

    /** @test */
    public function non_admin_cannot_list_macro_editors(): void
    {
        $this->actingAs($this->student)
            ->getJson('/api/webwork-macro-editors')
            ->assertJson(['message' => 'Only administrators can manage macro editors.']);
    }

    /** @test */
    public function editor_themselves_cannot_list_macro_editors(): void
    {
        $this->actingAs($this->editor)
            ->getJson('/api/webwork-macro-editors')
            ->assertJson(['message' => 'Only administrators can manage macro editors.']);
    }

    /** @test */
    public function unauthenticated_user_cannot_list_macro_editors(): void
    {
        $this->getJson('/api/webwork-macro-editors')
            ->assertUnauthorized();
    }

    /** @test */
    public function index_returns_editor_name_and_granted_by_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/webwork-macro-editors')
            ->assertJson(['type' => 'success'])
            ->json();

        $editor_entry = collect($response['editors'])
            ->firstWhere('user_id', $this->editor->id);

        $this->assertArrayHasKey('name', $editor_entry);
        $this->assertArrayHasKey('granted_by_name', $editor_entry);
        $this->assertArrayHasKey('email', $editor_entry);
    }

    // -------------------------------------------------------------------------
    // STORE
    // -------------------------------------------------------------------------

    /** @test */
    public function admin_can_grant_macro_editor_role(): void
    {
        $new_user = factory(User::class)->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/webwork-macro-editors', [
                'user' => "Jane Doe --- jane@example.com",
                'user_id' => $new_user->id,
            ])
            ->assertJson([
                'type' => 'success',
                'message' => 'Jane Doe has been granted the macro editor role.',
            ]);

        $this->assertDatabaseHas('webwork_macro_editors', [
            'user_id' => $new_user->id,
        ]);
    }

    /** @test */
    public function non_admin_cannot_grant_macro_editor_role(): void
    {
        $new_user = factory(User::class)->create([
            'email' => 'newuser@example.com',
        ]);

        $this->actingAs($this->student)
            ->postJson('/api/webwork-macro-editors', [
                'user' => "New User --- newuser@example.com",
                'user_id' => $new_user->id,
            ])
            ->assertJson(['message' => 'Only administrators can grant the macro editor role.']);

        $this->assertDatabaseMissing('webwork_macro_editors', [
            'user_id' => $new_user->id,
        ]);
    }

    /** @test */
    public function granting_editor_role_to_existing_editor_returns_error(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/webwork-macro-editors', [
                'user' => "{$this->editor->first_name} {$this->editor->last_name} --- {$this->editor->email}",
                'user_id' => $this->editor->id,
            ])
            ->assertJson([
                'type' => 'error',
                'message' => "{$this->editor->first_name} {$this->editor->last_name} is already a macro editor.",
            ]);
    }

    /** @test */
    public function editor_themselves_cannot_grant_macro_editor_role(): void
    {
        $new_user = factory(User::class)->create([
            'email' => 'another@example.com',
        ]);

        $this->actingAs($this->editor)
            ->postJson('/api/webwork-macro-editors', [
                'user' => "Another User --- another@example.com",
                'user_id' => $new_user->id,
            ])
            ->assertJson(['message' => 'Only administrators can grant the macro editor role.']);
    }

    // -------------------------------------------------------------------------
    // DESTROY
    // -------------------------------------------------------------------------

    /** @test */
    public function admin_can_revoke_macro_editor_role(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson("/api/webwork-macro-editors/{$this->macro_editor->id}")
            ->assertJson([
                'type' => 'info',
                'message' => "{$this->editor->first_name} {$this->editor->last_name}'s macro editor role has been revoked.",
            ]);

        $this->assertDatabaseMissing('webwork_macro_editors', [
            'id' => $this->macro_editor->id,
        ]);
    }

    /** @test */
    public function non_admin_cannot_revoke_macro_editor_role(): void
    {
        $this->actingAs($this->student)
            ->deleteJson("/api/webwork-macro-editors/{$this->macro_editor->id}")
            ->assertJson(['message' => 'Only administrators can revoke the macro editor role.']);

        $this->assertDatabaseHas('webwork_macro_editors', [
            'id' => $this->macro_editor->id,
        ]);
    }

    /** @test */
    public function editor_cannot_revoke_their_own_editor_role(): void
    {
        $this->actingAs($this->editor)
            ->deleteJson("/api/webwork-macro-editors/{$this->macro_editor->id}")
            ->assertJson(['message' => 'Only administrators can revoke the macro editor role.']);

        $this->assertDatabaseHas('webwork_macro_editors', [
            'id' => $this->macro_editor->id,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_revoke_macro_editor_role(): void
    {
        $this->deleteJson("/api/webwork-macro-editors/{$this->macro_editor->id}")
            ->assertUnauthorized();

        $this->assertDatabaseHas('webwork_macro_editors', [
            'id' => $this->macro_editor->id,
        ]);
    }
}
