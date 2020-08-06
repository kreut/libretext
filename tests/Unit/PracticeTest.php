<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PracticeTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testHelloWorld()
    {


       $this->assertEquals(2+2, 4);
    }

    public function testLaravelDevsIncludesDayle() {
        $names = ['Taylor', 'Shawn', 'Dayle'];
        $this->assertContains('Dayle', $names);
    }

    public function testFamilyRequiresParent() {
        $family = ['parents' => 'Joe',
                  'children' => ['Mike', 'John']];
        $this->assertArrayHasKey('parents', $family);
        $this->assertIsString($family['parents']);
    }


}
