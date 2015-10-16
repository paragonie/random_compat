<?php
class UtilityTest extends PHPUnit_Framework_TestCase
{
    public function testStrlen()
    {
        $this->assertEquals(RandomCompat_strlen("\xF0\x9D\x92\xB3"), 4);
        
        // To help illustrate the difference
        if (
            defined('MB_OVERLOAD_STRING') &&
            ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING
        ) {
            $this->assertTrue(
                strlen("\xF0\x9D\x92\xB3") === 1
            );
        } else {
            $this->assertTrue(
                strlen("\xF0\x9D\x92\xB3") === 4
            );
        }
    }
    
    public function testIntval()
    {
        // Equals
        $this->assertEquals(
            abs(RandomCompat_intval(-4.5)),
            abs(RandomCompat_intval(4.5))
        );
        
        // True
        $this->assertTrue(
            is_int(RandomCompat_intval(PHP_INT_MAX, true))
        );
        $this->assertTrue(
            is_int(RandomCompat_intval(~PHP_INT_MAX, true))
        );
        $this->assertTrue(
            is_int(RandomCompat_intval(~PHP_INT_MAX + 1, true))
        );
        
        // False
        $this->assertFalse(
            is_int(RandomCompat_intval((float) PHP_INT_MAX, true))
        );
        $this->assertFalse(
            is_int(RandomCompat_intval((float) ~PHP_INT_MAX, true))
        );
        $this->assertFalse(
            is_int(RandomCompat_intval(PHP_INT_MAX + 1, true))
        );
        $this->assertFalse(
            is_int(RandomCompat_intval(~PHP_INT_MAX - 1, true))
        );
        $this->assertFalse(
            is_int(RandomCompat_intval(PHP_INT_MAX - 0.01, true))
        );
        $this->assertFalse(
            is_int(RandomCompat_intval(~PHP_INT_MAX + 0.01, true))
        );
    }
}
