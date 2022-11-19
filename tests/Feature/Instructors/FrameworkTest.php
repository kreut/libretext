<?php

namespace Tests\Feature\Instructors;

use App\Framework;
use App\FrameworkLevel;
use App\User;
use Tests\TestCase;

class FrameworkTest extends TestCase
{

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->framework = factory(Framework::class)->create(['user_id' => $this->user->id]);
        $this->framework_level = factory(FrameworkLevel::class)->create(['framework_id' => $this->framework->id]);
        $this->framework_info = ['title' => 'some framework title',
            'descriptor_type' => 'concept',
            'description' => 'some description',
            'author' => 'some author',
            'license' => 'publicdomain',
            'source_url' => 'some url',
            'user_id' => $this->user->id];

    }

    /** @test */
    public
    function non_instructor_cannot_create_framework()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->user)
            ->postJson("/api/frameworks", $this->framework_info)
            ->assertJson(['message' => "You are not allowed to create a framework."]);

    }

    /** @test */
    public
    function framework_must_have_valid_properties()
    {
        $this->framework_info = ['title' => '',
            'descriptor_type' => 'bogus',
            'author' => '',
            'license' => 'bad license',
            'source_url' => '',
            'user_id' => $this->user->id];
        $this->actingAs($this->user)
            ->postJson("/api/frameworks", $this->framework_info)
            ->assertJsonValidationErrors('license')
            ->assertJsonValidationErrors('descriptor_type')
            ->assertJsonValidationErrors('author')
            ->assertJsonValidationErrors('source_url');
    }


    /** @test */
    public
    function owner_can_update_framework()
    {
        $this->framework_info['title'] = 'newer title';
        $this->actingAs($this->user)
            ->patchJson("/api/frameworks/{$this->framework->id}", $this->framework_info)
            ->assertJson(['message' => "{$this->framework->title} has been updated."]);


    }

    /** @test */
    public
    function non_owner_cannot_update_framework()
    {
        $this->framework_info['title'] = 'newer title';
        $this->actingAs($this->user_2)
            ->patchJson("/api/frameworks/{$this->framework->id}", $this->framework_info)
            ->assertJson(['message' => "You are not allowed to edit this framework."]);

    }

    /** @test */
    public
    function non_instructor_cannot_get_frameworks()
    {
        $this->user_2->role = 3;
        $this->user_2->save();
        $this->actingAs($this->user_2)
            ->getJson("/api/frameworks")
            ->assertJson(['message' => "You are not allowed to get the frameworks."]);


    }

    /** @test */
    public
    function instructor_can_get_frameworks()
    {
        $this->actingAs($this->user)
            ->getJson("/api/frameworks")
            ->assertJson(['type' => "success"]);

    }

    /** @test */
    public
    function non_instructor_cannot_view_frameworks()
    {
        $this->user_2->role = 3;
        $this->user_2->save();
        $this->actingAs($this->user_2)
            ->getJson("/api/frameworks/{$this->framework->id}")
            ->assertJson(['message' => "You are not allowed to view the framework."]);

    }

    /** @test */
    public
    function instructor_can_view_frameworks()
    {
        $this->actingAs($this->user)
            ->getJson("/api/frameworks/{$this->framework->id}")
            ->assertJson(['type' => "success"]);

    }


    /** @test */
    public function owner_can_destroy_framework()
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
        $this->assertDatabaseHas('framework_levels', ['framework_id' => $this->framework->id]);
        $this->actingAs($this->user)
            ->deleteJson("/api/frameworks/{$this->framework->id}")
            ->assertJson(['message' => "{$this->framework->title} has been deleted."]);
        $this->assertDatabaseMissing('framework_levels', ['framework_id' => $this->framework->id]);
    }


    /** @test */
    public
    function non_owner_cannot_destroy_framework()
    {
        $this->actingAs($this->user_2)
            ->deleteJson("/api/frameworks/{$this->framework->id}")
            ->assertJson(['message' => 'You are not allowed to delete this framework.']);


    }

    /** @test */
    public
    function non_instructor_cannot_export_frameworks()
    {
        $this->user_2->role = 3;
        $this->user_2->save();
        $this->actingAs($this->user_2)
            ->postJson("/api/frameworks/export/{$this->framework->id}")
            ->assertJson(['message' => 'You are not allowed to export this framework.']);
    }

}
