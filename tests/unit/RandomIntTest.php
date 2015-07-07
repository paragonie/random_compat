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
    
    public function testDistribution()
    {
        $integers = array_fill(0, 100, 0);
        for ($i = 0; $i < 10000; ++$i) {
            ++$integers[random_int(0,99)];
        }
        for ($i = 0; $i < 100; ++$i) {
            $this->assertFalse($integers[$i] < 30);
            $this->assertFalse($integers[$i] > 170);
        }
    }
    
    /**
     * This should be between 55% and 75%, always
     */
    public function testCoverage()
    {
        $integers = array_fill(0, 100, 0);
        for ($i = 0; $i < 100; ++$i) {
            ++$integers[random_int(0,99)];
        }
        $coverage = 0;
        for ($i = 0; $i < 100; ++$i) {
            if ($integers[$i] > 0) {
                ++$coverage;
            }
        }
        $this->assertTrue($coverage > 55);
        $this->assertTrue($coverage < 75);
    }
}