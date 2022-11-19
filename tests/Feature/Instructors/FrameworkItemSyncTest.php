<?php

namespace Tests\Feature\Instructors;

use App\Framework;
use App\FrameworkLevel;
use App\Question;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrameworkItemSyncTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->framework = factory(Framework::class)->create(['user_id' => $this->user->id]);
        $this->framework_level = factory(FrameworkLevel::class)->create(['framework_id' => $this->framework->id]);
        $this->question = factory(Question::class)->create(['question_editor_user_id' => $this->user->id, 'page_id' => 8347297]);
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
    public function non_instructor_cannot_get_questions_synced_to_descriptor()
    {
        $this->user->role = 3;
        $this->user->save();
        $framework_descriptor = DB::table('framework_descriptors')->first();
        $this->actingAs($this->user)
            ->getJson("/api/framework-item-sync-question/get-questions-by-descriptor/$framework_descriptor->id")
            ->assertJson(["message" => "You are not allowed to get the descriptors for that question."]);

    }

    /** @test */
    public function instructor_can_get_questions_synced_to_descriptor()
    {
        $framework_descriptor = DB::table('framework_descriptors')->first();
        $this->actingAs($this->user)
            ->getJson("/api/framework-item-sync-question/get-questions-by-descriptor/$framework_descriptor->id")
            ->assertJson(["type" => "success"]);


    }

    /** @test */
    public function non_instructor_cannot_get_framework_items_by_question()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->user)
            ->getJson("/api/framework-item-sync-question/question/{$this->question->id}")
            ->assertJson(["message" => "You are not allowed to get the framework alignments for the question."]);
    }

    /** @test */
    public function instructor_can_get_framework_items_by_question()
    {
        $this->actingAs($this->user)
            ->getJson("/api/framework-item-sync-question/question/{$this->question->id}")
            ->assertJson(["type" => "success"]);

    }
}
