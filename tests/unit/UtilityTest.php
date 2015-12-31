<?php
class UtilityTest extends PHPUnit_Framework_TestCase
{
    public function testStrlen()
    {
        if (!class_exists('Paragonie_Util_Binary')) {
            return $this->markTestSkipped(
                'We don\' need to test this in PHP 7.'
            );
        }
        $this->assertEquals(Paragonie_Util_Binary::strlen("\xF0\x9D\x92\xB3"), 4);
    }

    public function testIntval()
    {
        if (!class_exists('Paragonie_Util_Intval')) {
            return $this->markTestSkipped(
                'We don\' need to test this in PHP 7.'
            );
        }
        // Equals
        $this->assertEquals(
            abs($this->getIntval(-4.5)),
            abs($this->getIntval(4.5))
        );

        // True
        $this->assertTrue(
            is_int($this->getIntval(PHP_INT_MAX))
        );
        $this->assertTrue(
            is_int($this->getIntval(~PHP_INT_MAX))
        );
        $this->assertTrue(
            is_int($this->getIntval(~PHP_INT_MAX + 1))
        );
        $this->assertTrue(
            is_int($this->getIntval("1337e3"))
        );
        $this->assertTrue(
            is_int($this->getIntval("1."))
        );

        // False
        $this->assertFalse(
            is_int($this->getIntval((float) PHP_INT_MAX))
        );
        $this->assertFalse(
            is_int($this->getIntval((float) ~PHP_INT_MAX))
        );
        $this->assertFalse(
            is_int($this->getIntval(PHP_INT_MAX + 1))
        );
        $this->assertFalse(
            is_int($this->getIntval(~PHP_INT_MAX - 1))
        );
        $this->assertFalse(
            is_int($this->getIntval(~PHP_INT_MAX - 0.1))
        );
        $this->assertFalse(
            is_int($this->getIntval(PHP_INT_MAX + 0.1))
        );
        $this->assertFalse(
            is_int($this->getIntval("hello"))
        );

        if (PHP_INT_SIZE === 8) {
            $this->assertFalse(
                is_int($this->getIntval("-9223372036854775809"))
            );
            $this->assertTrue(
                is_int($this->getIntval("-9223372036854775808"))
            );
            $this->assertFalse(
                is_int($this->getIntval("9223372036854775808"))
            );
            $this->assertTrue(
                is_int($this->getIntval("9223372036854775807"))
            );
        } else {
            $this->assertFalse(
                is_int($this->getIntval("2147483648"))
            );
            $this->assertTrue(
                is_int($this->getIntval("2147483647"))
            );
            $this->assertFalse(
                is_int($this->getIntval("-2147483649"))
            );
            $this->assertTrue(
                is_int($this->getIntval("-2147483648"))
            );
        }
    }

    private function getIntval($value)
    {
        try {
            return Paragonie_Util_Intval::intval($value, __FUNCTION__, 1);
        } catch (TypeError $e) {
            return false;
        }
    }
}
