<?php
class RandomBytesTest extends PHPUnit_Framework_TestCase
{
    const NO_BASEDIR = 'There is no suitable CSPRNG installed on your system';
    
    public function testFuncExists()
    {
        $this->assertTrue(function_exists('random_bytes'));
    }
    
    public function testInvalidParams()
    {
        try {
            $bytes = random_bytes('good morning');
            $this->fail("random_bytes() should accept only an integer");
        } catch (TypeError $ex) {
            $this->assertTrue(true);
        } catch (Error $ex) {
            $this->assertTrue(true);
        } catch (Exception $ex) {
            $this->assertTrue(true);
            if ($ex->getMessage() === self::NO_BASEDIR) {
                return;
            }
        }
        
        try {
            $bytes = random_bytes(array(12));
            $this->fail("random_bytes() should accept only an integer");
        } catch (TypeError $ex) {
            $this->assertTrue(true);
        } catch (Error $ex) {
            $this->assertTrue(true);
        } catch (Exception $ex) {
            $this->assertTrue(true);
        }
        
        // This should succeed:
        try {
            $bytes = random_bytes('123456');
        } catch (Exception $ex) {
            $this->assertEquals(
                $ex->getMessage(),
                self::NO_BASEDIR
            );
        }
    }
    
    public function testOutput()
    {
        try {
            $bytes = array(
                random_bytes(12),
                random_bytes(64),
                random_bytes(64),
                random_bytes(1.5)
            );
        } catch (Exception $ex) {
            $this->assertEquals(
                $ex->getMessage(),
                self::NO_BASEDIR
            );
            return;
        }
        
        $this->assertTrue(
            strlen(bin2hex($bytes[0])) === 24
        );
        $this->assertTrue(
            strlen(bin2hex($bytes[3])) === 2
        );
        
        // This should never generate identical byte strings
        $this->assertFalse(
            $bytes[1] === $bytes[2]
        );
        
        try {
            $x = random_bytes(~PHP_INT_MAX - 1000000000);
            $this->fail("Integer overflow (~PHP_INT_MAX - 1000000000).");
        } catch (TypeError $ex) {
            $this->assertTrue(true);
        } catch (Error $ex) {
            $this->assertTrue(true);
        } catch (Exception $ex) {
            $this->assertTrue(true);
        }
        
        try {
            $x = random_bytes(PHP_INT_MAX + 1000000000);
            $this->fail("Requesting too many bytes should fail.");
        } catch (TypeError $ex) {
            $this->assertTrue(true);
        } catch (Error $ex) {
            $this->assertTrue(true);
        } catch (Exception $ex) {
            $this->assertTrue(true);
        }
    }
}
