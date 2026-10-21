<?php

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class WelcomeTest extends DuskTestCase
{
    #[Test]
    public function basic_test()
    {
        $this->browse(function ($browser) {
            $browser->visit('/')
                ->waitFor('.title', 1)
                ->assertSee('Laravel');
        });
    }
}
