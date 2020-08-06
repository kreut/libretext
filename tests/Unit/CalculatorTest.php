<?php

namespace Tests\Unit;
use http\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class Calculator
{
    protected $result = 0;


    public function getResult()
    {
        return $this->result;
    }

    public function add($num)
    {
        if (!is_numeric($num))
            throw new \InvalidArgumentException;
        $this->result += $num;

    }
}



class CalculatorTest extends TestCase
{

    public function setUp()
    {

        $this->calc = new Calculator();
        var_dump($this->calc);
    }

        public function testInstance()
        {

            $this->assertIsObject($this->calc);

        }
    /*

        public function testResultDefaultsToZero()
        {

            $this->assertSame(0, $this->calc->getResult());
        }

        public function testAddsNumbers()
        {

            $this->calc->add(5);
            $this->assertEquals(5, $this->calc->getResult());
        }

        /*
         public function testRequiresNumericValue()
         {
             $this->calc->add('five');
             $this->expectException(InvalidArgumentException::class);
         }
        */
}
