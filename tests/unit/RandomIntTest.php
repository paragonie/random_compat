<?php
class RandomIntTest extends PHPUnit_Framework_TestCase
{
    public function testFuncExists()
    {
        $this->assertTrue(function_exists('random_int'));
    }
    
    public function testOutput()
    {
        $integers = [
            random_int(0, 1000),
            random_int(1001,2000)
        ];
        
        $this->assertFalse($integers[0] === $integers[1]);
        $this->assertTrue($integers[0] <= 1000 && $integers[0] >= 0);
        $this->assertTrue($integers[1] < 2001 && $integers[1] > 1000);
    }
}