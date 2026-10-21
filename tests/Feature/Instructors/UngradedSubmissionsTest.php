<?php

namespace Tests\Feature\Instructors;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Course;
use App\User;
use Tests\TestCase;

class UngradedSubmissionsTest extends TestCase
{
    /**
     * @var Collection|Model|mixed
     */
    private $course;
    /**
     * @var Collection|Model|mixed
     */
    private $user_2;
    /**
     * @var Collection|Model|mixed
     */
    private $user;

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
    }

    #[Test]
    public function non_owner_cannot_get_ungraded_submissions(){
        $this->actingAs($this->user_2)->getJson("api/submission-files/ungraded-submissions/{$this->course->id}")
            ->assertJson(['message'=> 'You are not allowed to get the ungraded submissions for this course.']);

    }

    #[Test]
    public function owner_can_get_ungraded_submissions(){
        $this->actingAs($this->user)->getJson("api/submission-files/ungraded-submissions/{$this->course->id}")
            ->assertJson(['type'=> 'success']);

    }
}
