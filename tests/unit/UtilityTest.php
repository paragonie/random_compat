<?php
class UtilityTest extends PHPUnit_Framework_TestCase
{
    public function testStrlen()
    {
        if (!function_exists('RandomCompat_strlen')) {
            return $this->markTestSkipped(
                'We don\' need to test this in PHP 7.'
            );
        }
        $this->assertEquals(RandomCompat_strlen("\xF0\x9D\x92\xB3"), 4);
    }
    
    public function testIntval()
    {
        if (!function_exists('RandomCompat_intval')) {
            return $this->markTestSkipped(
                'We don\' need to test this in PHP 7.'
            );
        }
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
