<?php

namespace Tests\Feature\Instructors;

use App\Framework;
use App\FrameworkDescriptor;
use App\FrameworkLevel;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrameworkDescriptorTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->framework = factory(Framework::class)->create(['user_id' => $this->user->id]);
        $this->framework_level = factory(FrameworkLevel::class)->create(['framework_id' => $this->framework->id]);


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
    public function non_owner_cannot_delete_descriptor()
    {
        $descriptor = FrameworkDescriptor::first();
        $this->actingAs($this->user_2)
            ->deleteJson("/api/framework-descriptors/$descriptor->id")
            ->assertJson(["message" => "You are not allowed to remove that descriptor from the framework."]);

    }

    /** @test */
    public function owner_can_delete_descriptor()
    {
        $descriptor = FrameworkDescriptor::first();
        $this->actingAs($this->user)
            ->deleteJson("/api/framework-descriptors/$descriptor->id")
            ->assertJson(["message" => "'$descriptor->descriptor' has been removed from your framework."]);


    }

    /** @test */
    public function move_to_and_from_locations_must_be_valid()
    {
        $descriptor = DB::table('framework_descriptors')->first();
        $level_from_id = DB::table('framework_level_framework_descriptor')
            ->where('framework_descriptor_id', $descriptor->id)
            ->first()
            ->framework_level_id;
        $level_to_id = FrameworkLevel::where('id', '<>', $level_from_id)->first()->id;
        $data = ['level_from_id' => -1,
            'level_to_id' => $level_to_id,
            'descriptor_id' => $descriptor->id];

        $this->actingAs($this->user)
            ->patchJson("/api/framework-descriptors/move", $data)
            ->assertJson(["message" => "That descriptor doesn't exist in one of your frameworks."]);
        $data = ['level_from_id' => $level_from_id,
            'level_to_id' => -1,
            'descriptor_id' => $descriptor->id];

        $this->actingAs($this->user)
            ->patchJson("/api/framework-descriptors/move", $data)
            ->assertJson(["message" => "You cannot move the descriptor to a framework level that you do not own."]);
    }

    /** @test */
    public function owner_can_move_descriptor()
    {
        $descriptor = DB::table('framework_descriptors')->first();
        $level_from_id = DB::table('framework_level_framework_descriptor')
            ->where('framework_descriptor_id', $descriptor->id)
            ->first()
            ->framework_level_id;
        $level_to = FrameworkLevel::where('id', '<>', $level_from_id)->first();
        $data = ['level_from_id' => $level_from_id,
            'level_to_id' => $level_to->id,
            'descriptor_id' => $descriptor->id];

        $this->actingAs($this->user)
            ->patchJson("/api/framework-descriptors/move", $data)
            ->assertJson(["message" => "The descriptor '$descriptor->descriptor' has been moved to $level_to->title."]);

    }

}
