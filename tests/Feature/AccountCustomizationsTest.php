<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\User;
use Tests\TestCase;

class AccountCustomizationsTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['role' =>3]);

    }

  #[Test]
    public function non_instructor_cannot_update_account_customizations() {

            $this->actingAs($this->user)->patchJson("/api/account-customizations")
                ->assertJson(['message' => 'You are not allowed to update account customizations.']);
    }
}
