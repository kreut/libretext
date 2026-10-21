<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    #[Test]
    public function set_locale_from_header()
    {
        $this->withHeaders(['Accept-Language' => 'zh-CN'])
            ->postJson('/api/login');

        $this->assertEquals('zh-CN', $this->app->getLocale());
    }

    #[Test]
    public function set_locale_from_header_short()
    {
        $this->withHeaders(['Accept-Language' => 'en-US'])
            ->postJson('/api/login');

        $this->assertEquals('en', $this->app->getLocale());
    }
}
