<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    use CreatesApplication;
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('key_secrets')->insertOrIgnore([
            'key' => 'forge',
            'secret' => 'test-secret-for-forge'
        ]);
    }
}
