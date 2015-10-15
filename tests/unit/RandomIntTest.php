<?php
class RandomIntTest extends PHPUnit_Framework_TestCase
{
    public function testFuncExists()
    {
        $this->assertTrue(function_exists('random_int'));
    }
    
    public function testOutput()
    {
        $half_neg_max = (~PHP_INT_MAX / 2);
        $integers = array(
            random_int(0, 1000),
            random_int(1001,2000),
            random_int(-100, -10),
            random_int(-1000, 1000),
            random_int(~PHP_INT_MAX, PHP_INT_MAX),
            random_int($half_neg_max, PHP_INT_MAX)
        );
        
        $this->assertFalse($integers[0] === $integers[1]);
        $this->assertTrue($integers[0] >= 0 && $integers[0] <= 1000);
        $this->assertTrue($integers[1] >= 1001 && $integers[1] <= 2000);
        $this->assertTrue($integers[2] >= -100 && $integers[2] <= -10);
        $this->assertTrue($integers[3] >= -1000 && $integers[3] <= 1000);
        $this->assertTrue($integers[4] >= ~PHP_INT_MAX && $integers[4] <= PHP_INT_MAX);
        $this->assertTrue($integers[5] >= $half_neg_max && $integers[5] <= PHP_INT_MAX);
    }

    /**
     * @return array[]
     */
    public function typeErrorProvider()
    {
        return array(
            array(1, 2.0),
            array(1, 1.0),
            array(-1, "2"),
            array(-1, "3.1"),
            array(-2.0, 5),
            array(1.0, 5),
            array("-2", 5),
            array("3.1", 5),
        );
    }
    
    /**
     * @dataProvider typeErrorProvider
     * @expectedException TypeError
     */
    public function testTypeErrors($min, $max)
    {
        random_int($min, $max);
    }
    
    /**
     * @return array[]
     */
    public function rangeErrorProvider()
    {
        return array(
            array(1, 0),
            array(-999, -1000),
        );
    }
    
    /**
     * @dataProvider rangeErrorProvider
     * @expectedException Error
     */
    public function testRangeErrors($min, $max)
    {
        random_int($min, $max);
    }
    
}
