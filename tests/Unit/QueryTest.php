<?php

namespace Tests\Unit;


use App\User;
use App\Question;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class QueryTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();


    }

    /** @test */

    public function will_give_an_error_for_bad_page_ids() {


    }
    /** @test */
    public function can_save_a_public_query_technology_to_the_database()
    {
        $this->actingAs($this->user)->postJson('/api/questions/getQuestionsByTags',[
            'tags' => ['drag text']
        ])->assertJson(['type' => 'success']);

        $Question = new Question();
        $this->assertTrue($Question->where('page_id', '102682')->get()->isNotEmpty());

    }
/** @test */
    public function can_save_a_private_query_technology_to_the_database()
    {
        $this->actingAs($this->user)->postJson('/api/questions/getQuestionsByTags',[
            'tags' => ['drag text']
        ])->assertJson(['type' => 'success']);

        $Question = new Question();
        $this->assertTrue($Question->where('page_id', '102654')->get()->isNotEmpty());
    }
/** @test */
    public function can_save_a_frankenstein_technology_to_the_database()
    {
        Storage::disk('public')->delete('102686.html');

        $this->actingAs($this->user)->postJson('/api/questions/getQuestionsByTags',[
            'tags' => ['id=102686']
        ])->assertJson(['type' => 'success']);

        //exists in the database and the file exists
        $Question = new Question();
        $question = $Question->where('page_id', '102686')->get()[0];
        $this->assertEquals(1, $question->non_technology);
        $this->assertEquals('<iframe src="https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&amp;id=1" frameborder="0" allowfullscreen="allowfullscreen"></iframe>', $question->technology_iframe);
        $this->assertTrue( Storage::disk('public')->exists('102686.html'));
        Storage::disk('public')->delete('102686.html');
    }
/** @test */
    public function can_save_a_non_technology_query_to_the_database()
    {
        Storage::disk('public')->delete('102686.html');

        $this->actingAs($this->user)->postJson('/api/questions/getQuestionsByTags',[
            'tags' => ['id=102685']
        ])->assertJson(['type' => 'success']);

        //exists in the database and the file exists
        $Question = new Question();
        $question = $Question->where('page_id', '102685')->first()->get()[0];


        $this->assertEquals(1, $question->non_technology);
        $this->assertEquals($question->technology_iframe, '');
        $this->assertTrue( Storage::disk('public')->exists('102685.html'));
        Storage::disk('public')->delete('102685.html');


    }


}
