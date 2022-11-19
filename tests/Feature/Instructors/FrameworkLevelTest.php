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

    /** @test */
    public function descriptors_will_not_be_repeated_within_a_framework()
    {
        $this->_createFrameworkAndLevels();
        $data = ['title_1' => 'title 1',
            'title_2' => '',
            'title_3' => '',
            'title_4' => '',
            'descriptor' => 'D4',
            'framework_id' => $this->framework->id];
        $this->actingAs($this->user)->postJson('/api/framework-levels/with-descriptors', $data)
            ->assertJson(['message' => 'Descriptor already exists in this framework.']);

    }

    /** @test */
    public function descriptor_action_must_be_valid()
    {
        $this->_createFrameworkAndLevels();
        $framework_levels_info = DB::table('framework_levels')
            ->join('framework_level_framework_descriptor', 'framework_levels.id', '=', 'framework_level_framework_descriptor.framework_level_id')
            ->where('framework_levels.title', 'yet another thing')
            ->first();

        $this->actingAs($this->user)
            ->deleteJson("/api/framework-levels/$framework_levels_info->framework_level_id/descriptor-action/bad-action/level-to-move-to/1")
            ->assertJson(['message' => "bad-action is not a valid descriptor action."]);


    }


    /** @test */
    public function only_the_owner_can_upload()
    {

        $upload_info = ['framework_id' => $this->framework->id,
            'csv_file_array' => [['Bad heading' => '', 'Level 2' => '', 'Level 3' => '', 'Level 4', 'Descriptor' => '']]
        ];
        $this->actingAs($this->user_2)
            ->putJson("/api/framework-levels/upload", $upload_info)
            ->assertJson(['message' => 'You are not allowed to upload to this framework.']);


    }

    /** @test */
    public function an_upload_needs_correct_headings()
    {

        $upload_info = ['framework_id' => $this->framework->id,
            'csv_file_array' => [['Bad heading' => '', 'Level 2' => '', 'Level 3' => '', 'Level 4' => '', 'Descriptor' => '']]
        ];
        $response = json_decode($this->actingAs($this->user)
            ->putJson("/api/framework-levels/upload", $upload_info)
            ->getContent(), 1);

        $this->assertEquals(["The .csv heading should be: Level 1, Level 2, Level 3, Level 4, Descriptor."], $response['message']);


    }

    /** @test */
    public function csv_should_not_have_missing_entries_within_the_structure()
    {

        $upload_info = ['framework_id' => $this->framework->id,
            'csv_file_array' => [['Level 1' => '', 'Level 2' => 'something here', 'Level 3' => '', 'Level 4' => '', 'Descriptor' => '']]
        ];
        $response = json_decode($this->actingAs($this->user)
            ->putJson("/api/framework-levels/upload", $upload_info)->getContent(), 1);
        $this->assertEquals(["Level 1 needs an entry since it is not the highest level in row 1."], $response['message']);
    }


    /** @test */
    public function title_cannot_be_repeated_at_a_level_when_updating()
    {
        $this->_createFrameworkAndLevels();
        $framework_level = DB::table('framework_levels')->where('title', '<>', 'eee')->first();
        $framework_level_info = ['description' => 'some description',
            'framework_level_id' => $framework_level->id,
            'title' => 'eee',
        ];
        $this->actingAs($this->user)
            ->patchJson("/api/framework-levels", $framework_level_info)
            ->assertJsonValidationErrors('title');

    }


    /** @test */
    public function parent_id_must_be_correct_based_on_level_to_store_with_descriptors()
    {
        $framework_level_info = ['description' => 'some description',
            'framework_id' => $this->framework->id,
            'level_to_add' => 4,
            'order' => 1,
            'title' => 'some title',
            'parent_id' => DB::table('framework_levels')->where('level', 1)->first()->parent_id
        ];
        $this->actingAs($this->user)
            ->postJson("/api/framework-levels", $framework_level_info)
            ->assertJson(['message' => "The framework level to add does not seem be correct.  Please contact us."]);

    }


    /** @test */
    public function cannot_move_if_there_are_too_many_sublevels()
    {
        $this->_createFrameworkAndLevels();
        $level_from = DB::table('framework_levels')->where('title', 'eee')->first();
        $level_to = DB::table('framework_levels')->where('title', 'level 3 thing')->first();
        $framework_level_info = [
            "level_from_id" => $level_from->id,
            "level_to_id" => $level_to->id,
            "move_to_option_is_top_level" => 0
        ];

        $this->actingAs($this->user)
            ->patchJson("/api/framework-levels/move-level", $framework_level_info)
            ->assertJson(['message' => "Your original level has 4 children levels so it can't be added to the framework at level $level_to->level. Otherwise, the total number of levels would exceed 4."]);

    }

    /** @test */
    public function cannot_move_to_lower_level_within_a_given_level()
    {
        $this->_createFrameworkAndLevels();
        $higher_framework_level = DB::table('framework_levels')->where('title', 'another thing')->first();
        $lower_framework_level = DB::table('framework_levels')->where('title', 'yet another thing')->first();
        $framework_level_info = [
            "level_from_id" => $higher_framework_level->id,
            "level_to_id" => $lower_framework_level->id,
            "move_to_option_is_top_level" => 0
        ];

        $this->actingAs($this->user)
            ->patchJson("/api/framework-levels/move-level", $framework_level_info)
            ->assertJson(['message' => "You can't move lower within the same level."]);
    }


    /** @test */
    public function cannot_move_to_the_same_level()
    {
        $this->_createFrameworkAndLevels();
        $framework_level = DB::table('framework_levels')->first();
        $framework_level_info = [
            "level_from_id" => $framework_level->id,
            "level_to_id" => $framework_level->id,
            "move_to_option_is_top_level" => 0
        ];

        $this->actingAs($this->user)
            ->patchJson("/api/framework-levels/move-level", $framework_level_info)
            ->assertJson(['message' => "The to and from levels are the same."]);


    }

    /** @test */
    public function non_owner_cannot_move_levels()
    {
        $this->_createFrameworkAndLevels();

        $framework_level_info = [
            "level_from_id" => -1,
            "level_to_id" => DB::table('framework_levels')->first()->id,
            "move_to_option_is_top_level" => 0
        ];

        $this->actingAs($this->user)
            ->patchJson("/api/framework-levels/move-level", $framework_level_info)
            ->assertJson(['message' => "You do not own the 'level from' framework level'."]);

        $framework_level_info = [
            "level_from_id" => DB::table('framework_levels')->first()->id,
            "level_to_id" => 1000000,
            "move_to_option_is_top_level" => 0
        ];

        $this->actingAs($this->user)
            ->patchJson("/api/framework-levels/move-level", $framework_level_info)
            ->assertJson(['message' => "You do not own the 'level to' framework level'."]);

    }


    /** @test */
    public function non_owner_cannot_change_framework_level_position_within_level()
    {
        $framework_level_info = [
            "level_id" => 1,
            "position" => 1,
        ];

        $this->actingAs($this->user_2)
            ->patchJson("/api/framework-levels/change-position", $framework_level_info)
            ->assertJson(['message' => "You are not allowed to change the position of that framework level."]);


    }

    /** @test */
    public function non_owner_cannot_store_framework_level()
    {
        $this->_createFrameworkAndLevels();

        $framework_level_info = [
            "order" => 0,
            "title" => "some new level",
            "level_to_add" => 1,
            "framework_id" => $this->framework->id,
            "description" => "some descriptrion",
            "parent_id" => 0,
        ];

        $this->actingAs($this->user_2)
            ->postJson("/api/framework-levels/", $framework_level_info)
            ->assertJson(['message' => "You are not allowed to add new framework levels to this framework."]);


    }

    /** @test */
    public function level_must_be_valid_when_storing()
    {
        $this->_createFrameworkAndLevels();

        $framework_level_info = [
            "order" => 0,
            "title" => "some new level",
            "level_to_add" => 400,
            "framework_id" => $this->framework->id,
            "description" => "some descriptrion",
            "parent_id" => 0,
        ];

        $this->actingAs($this->user)
            ->postJson("/api/framework-levels/", $framework_level_info)
            ->assertJson(['message' => "400 is not a valid level."]);

    }

    /** @test */
    public function parent_id_and_level_should_match()
    {
        $this->_createFrameworkAndLevels();

        $framework_level_info = [
            "order" => 0,
            "title" => "some new level",
            "level_to_add" => 3,
            "framework_id" => $this->framework->id,
            "description" => "some description",
            "parent_id" => 0,
        ];

        $this->actingAs($this->user)
            ->postJson("/api/framework-levels/", $framework_level_info)
            ->assertJson(['message' => 'The framework level to add does not seem be correct.  Please contact us.']);

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


    /** @test */
    public function owner_can_change_framework_level_position_within_level()
    {
        $this->_createFrameworkAndLevels();
        $framework_level = DB::table('framework_levels')->where('title', 'yet another level')->first();

        $framework_level_info = [
            "level_id" => $framework_level->id,
            "position" => 1,
        ];

        $this->actingAs($this->user)
            ->patchJson("/api/framework-levels/change-position", $framework_level_info)
            ->assertJson(['message' => "The position has been updated."]);
        $framework_level = DB::table('framework_levels')->where('title', 'yet another level')->first();
        $this->assertEquals(2, $framework_level->order);

    }

    /** @test */
    public function non_owner_cannot_store_level_with_descriptor()
    {
        $framework_level_info = ['description' => 'some description',
            'framework_id' => $this->framework->id,
            'level_to_add' => 1,
            'order' => 1,
            'title' => 'some title 2',
            'parent_id' => DB::table('framework_levels')->where('level', 1)->first()->parent_id
        ];
        $this->actingAs($this->user_2)
            ->postJson("/api/framework-levels", $framework_level_info)
            ->assertJson(['message' => "You are not allowed to add new framework levels to this framework."]);
    }


    /** @test */
    public function title_cannot_be_repeated_at_a_level()
    {
        $framework_level_info = ['description' => 'some description',
            'framework_id' => $this->framework->id,
            'level_to_add' => 1,
            'order' => 1,
            'title' => 'some title',
            'parent_id' => DB::table('framework_levels')->where('level', 1)->first()->parent_id
        ];
        $this->actingAs($this->user)
            ->postJson("/api/framework-levels", $framework_level_info)
            ->assertJsonValidationErrors('title');
    }

    /** @test */
    public function optionally_delete_associated_descriptors()
    {
        $this->_createFrameworkAndLevels();
        $framework_levels_info = DB::table('framework_levels')
            ->join('framework_level_framework_descriptor', 'framework_levels.id', '=', 'framework_level_framework_descriptor.framework_level_id')
            ->where('framework_levels.title', 'yet another thing')
            ->first();

        $descriptor_id = $framework_levels_info->framework_descriptor_id;
        $title = $framework_levels_info->title;
        $framework_level_id = $framework_levels_info->framework_level_id;
        $this->actingAs($this->user)
            ->deleteJson("/api/framework-levels/$framework_level_id/descriptor-action/delete/level-to-move-to/0")
            ->assertJson(['message' => "$title and all associated descriptors have been deleted."]);
        $this->assertDatabaseMissing('framework_descriptors', ['id' => $descriptor_id]);
        $this->assertDatabaseMissing('framework_levels', ['id' => $framework_level_id]);
    }

    /** @test */
    public function optionally_move_associated_descriptors_to_a_new_level()
    {
        $this->_createFrameworkAndLevels();

        $framework_levels_info = DB::table('framework_levels')
            ->join('framework_level_framework_descriptor', 'framework_levels.id', '=', 'framework_level_framework_descriptor.framework_level_id')
            ->where('framework_levels.title', 'yet another thing')
            ->first();

        $descriptor_id = $framework_levels_info->framework_descriptor_id;
        $title = $framework_levels_info->title;
        $level_to_move_to = FrameworkLevel::where('parent_id', 0)->first();
        $framework_level_id = $framework_levels_info->framework_level_id;
        $this->actingAs($this->user)
            ->deleteJson("/api/framework-levels/$framework_level_id/descriptor-action/move/level-to-move-to/$level_to_move_to->id")
            ->assertJson(['message' => "'$title' has been deleted and the descriptors have been moved to  '$level_to_move_to->title'."]);
        $this->assertDatabaseHas('framework_level_framework_descriptor', ['framework_level_id' => $level_to_move_to->id, 'framework_descriptor_id' => $descriptor_id]);
        $this->assertDatabaseMissing('framework_levels', ['id' => $framework_level_id]);
    }


}
