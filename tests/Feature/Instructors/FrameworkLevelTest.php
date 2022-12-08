<?php

namespace Tests\Feature\Instructors;

use App\Framework;
use App\FrameworkLevel;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrameworkLevelTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->framework = factory(Framework::class)->create(['user_id' => $this->user->id]);
        $this->framework_level = factory(FrameworkLevel::class)->create(['framework_id' => $this->framework->id]);


    }

    private function _createFrameworkAndLevels()
    {
        $framework_levels_and_descriptors = json_decode('[{"Level 1":"Some level","Level 2":"","Level 3":"","Level 4":"","Descriptor":""},{"Level 1":"eee","Level 2":"","Level 3":"","Level 4":"","Descriptor":"D4"},{"Level 1":"eee","Level 2":"some other level 2","Level 3":"","Level 4":"","Descriptor":""},{"Level 1":"eee","Level 2":"another level 2f","Level 3":"","Level 4":"","Descriptor":"some new descriptor"},{"Level 1":"eee","Level 2":"another level 2f","Level 3":"another thing","Level 4":"","Descriptor":"wefwefwef"},{"Level 1":"eee","Level 2":"another level 2f","Level 3":"another thing","Level 4":"","Descriptor":"New descriptor"},{"Level 1":"eee","Level 2":"another level 2f","Level 3":"another thing","Level 4":"","Descriptor":"Even a better one"},{"Level 1":"eee","Level 2":"another level 2f","Level 3":"another thing","Level 4":"","Descriptor":"bets descriptor"},{"Level 1":"eee","Level 2":"another level 2f","Level 3":"another thing","Level 4":"yet another thing","Descriptor":"better things to do"},{"Level 1":"eee","Level 2":"another level 2f","Level 3":"another thing","Level 4":"Some other level","Descriptor":""},{"Level 1":"eee","Level 2":"wfwefewf","Level 3":"","Level 4":"","Descriptor":""},{"Level 1":"eee","Level 2":"yet another level","Level 3":"","Level 4":"","Descriptor":""},{"Level 1":"Basic stats555","Level 2":"","Level 3":"","Level 4":"","Descriptor":"another oneklkjjlkj"},{"Level 1":"Basic stats555","Level 2":"","Level 3":"","Level 4":"","Descriptor":"something else"},{"Level 1":"Basic stats555","Level 2":"something elsesfgggg","Level 3":"","Level 4":"","Descriptor":"Yippie"},{"Level 1":"Basic stats555","Level 2":"level 3 thing","Level 3":"","Level 4":"","Descriptor":""},{"Level 1":"blah basdfsdfdsf","Level 2":"","Level 3":"","Level 4":"","Descriptor":"something"},{"Level 1":"blah basdfsdfdsf","Level 2":"new level 3","Level 3":"","Level 4":"","Descriptor":""}]', 1);
        foreach ($framework_levels_and_descriptors as $value) {
            $data = ['title_1' => $value['Level 1'],
                'title_2' => $value['Level 2'],
                'title_3' => $value['Level 3'],
                'title_4' => $value['Level 4'],
                'descriptor' => $value['Descriptor'],
                'framework_id' => $this->framework->id];
            $result = $this->actingAs($this->user)->postJson('/api/framework-levels/with-descriptors', $data);
            if (!$result->assertJson(['type' => 'success'])) {
                dd('framework levels not stored.');
            }
        }
    }
    public function level_must_be_valid_when_storing()
    {


    }
/** @test */
    public function non_owner_cannot_store_framework_level()
    {        $this->actingAs($this->user_2)
        ->postJson("/api/framework-levels/")
        ->assertJson(['message' => "$title and all associated descriptors have been deleted."]);


    }

    public function stored_framework_level_description_should_be_valid()
    {


    }

    public function updated_framework_level_description_should_be_valid()
    {


    }


    /** @test */
    public function optionally_delete_associated_descriptors()
    {
        $this->_createFrameworkAndLevels();
        $framework_levels_info = DB::table('framework_levels')
            ->join('framework_level_framework_descriptor', 'framework_levels.id', '=', 'framework_level_framework_descriptor.framework_descriptor_id')
            ->where('framework_levels.title', 'yet another thing')
            ->first();

        $descriptor_id = $framework_levels_info->framework_descriptor_id;
        $title = $framework_levels_info->title;
        $framework_level_id = $framework_levels_info->framework_level_id;
        $this->actingAs($this->user)
            ->deleteJson("/api/framework-levels/$framework_levels_info->framework_level_id/descriptor-action/delete/level-to-move-to/1")
            ->assertJson(['message' => "$title and all associated descriptors have been deleted."]);
        $this->assertDatabaseMissing('framework_descriptors', ['id' => $descriptor_id]);
        $new_level_parent_id = FrameworkLevel::find(1)->parent_id;
        $this->assertDatabasehas('framework_levels', ['id' => $framework_level_id, 'parent_id' => $new_level_parent_id]);


    }

    /** @test */
    public function optionally_move_associated_descriptors_to_a_new_level()
    {
        $this->_createFrameworkAndLevels();
        $framework_levels_info = DB::table('framework_levels')
            ->join('framework_level_framework_descriptor', 'framework_levels.id', '=', 'framework_level_framework_descriptor.framework_descriptor_id')
            ->where('framework_levels.title', 'yet another thing')
            ->first();

        $descriptor_id = $framework_levels_info->framework_descriptor_id;
        $title = $framework_levels_info->title;
        $new_level = FrameworkLevel::find(1);
        $framework_level_id = $framework_levels_info->framework_level_id;
        $this->actingAs($this->user)
            ->deleteJson("/api/framework-levels/$framework_levels_info->framework_level_id/descriptor-action/move/level-to-move-to/1")
            ->assertJson(['message' => "'$title' has been deleted and the descriptors have been moved to  '$new_level->title"]);
        $this->assertDatabaseHas('framework_level_framework_descriptor', ['framework_level_id' => $framework_level_id, 'framework_descriptor_id' => $descriptor_id]);

    }

    /** @test */
    public
    function owner_can_update_framework_level()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/framework-levels", ['title' => 'newer title', 'framework_level_id' => $this->framework_level->id])
            ->assertJson(['message' => "{$this->framework_level->title} has been changed to newer title."]);


    }

    /** @test */
    public
    function non_owner_cannot_update_framework_level()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/framework-levels", ['title' => 'newer title', 'framework_level_id' => $this->framework_level->id])
            ->assertJson(['message' => "You are not allowed to update that framework level."]);

    }

    /** @test */
    public function non_owner_cannot_delete_framework_level()
    {

        $this->actingAs($this->user_2)
            ->deleteJson("/api/framework-levels/{$this->framework_level->id}/descriptor-action/delete/level-to-move-to/0")
            ->assertJson(['message' => "You are not allowed to delete that framework level."]);

    }

    /** @test */
    public function non_owner_cannot_get_all_children()
    {
        $this->actingAs($this->user_2)
            ->getJson("/api/framework-levels/all-children/{$this->framework_level->id}")
            ->assertJson(['message' => "You are not allowed to get the children for this framework level."]);

    }

    /** @test */
    public function owner_can_get_all_children()
    {
        $this->actingAs($this->user)
            ->getJson("/api/framework-levels/all-children/{$this->framework_level->id}")
            ->assertJson(['type' => "success"]);

    }


    /** @test */
    public function non_owner_cannot_get_all_levels_from_same_parent()
    {
        $this->actingAs($this->user_2)
            ->getJson("/api/framework-levels/same-parent/{$this->framework_level->id}")
            ->assertJson(['message' => "You are not allowed to get the framework levels with the same parent of the current level."]);

    }

    /** @test */
    public function owner_can_get_all_levels_from_same_parent()
    {
        $this->actingAs($this->user)
            ->getJson("/api/framework-levels/same-parent/{$this->framework_level->id}")
            ->assertJson(['type' => "success"]);

    }



    public function descriptor_action_must_be_valid()
    {


    }


    public function owner_can_change_framework_level_position_within_level()
    {


    }

    public function non_owner_cannot_change_framework_level_position_within_level()
    {


    }

    public function non_owner_cannot_move_levels()
    {


    }


    public function cannot_move_to_the_same_level()
    {


    }

    public function cannot_move_to_lower_level_within_a_given_level()
    {


    }

    public function cannot_move_if_there_are_too_many_sublevels()
    {


    }

    public function non_owner_cannot_store_with_descriptors()
    {


    }

    public function cannot_store_descriptors_with_empty_levels()
    {


    }

    public function will_not_re_add_descriptor()
    {


    }

    public function an_upload_needs_correct_headings()
    {


    }

    public function csv_should_not_be_empty()
    {


    }

    public function csv_should_not_have_missing_entries_within_the_structure()
    {


    }


}
