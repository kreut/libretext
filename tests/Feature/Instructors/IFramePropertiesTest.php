<?php

namespace Tests\Feature\Instructors;


use App\User;
use App\Course;
use Tests\TestCase;
use App\Traits\Test;

class IFramePropertiesTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);


    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function non_owner_cannot_update_iframe_properties()
    {

        $this->actingAs($this->user_2)->patchJson("/api/courses/{$this->course->id}/iframe-properties",
        ['item' => 'submission',
            'action' => 'show'])
            ->assertJson(['message' => 'You are not allowed to update what is shown in the iframe.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function iframe_property_must_be_valid()
    {

        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}/iframe-properties",
            ['item' => 'bogus item',
                'action' => 'show'])
            ->assertJson(['message' => 'bogus item is not a valid iframe property.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function owner_can_update_iframe_properties()
    {

        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}/iframe-properties",
            ['item' => 'submission',
                'action' => 'show'])
            ->assertJson(['message' => 'The submission information will now be shown when embedded in an iframe.']);
    }


}
