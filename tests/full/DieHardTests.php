<?php
class DieHardTests extends PHPUnit_Framework_TestCase
{
    /**
     * Birthday spacings: Choose random points on a large interval. 
     * The spacings between the points should be asymptotically exponentially
     * distributed.
     */
    public function testBirthday()
    {
        $buckets = array_fill(0, 20, 0);
        for ($i = 0; $i < 100000; ++$i) {
            $random = random_int(0, 999);
            $bucket = (int) floor($random / 100);
            ++$buckets[$bucket];
        }
        for ($i = 0; $i < 10; ++$i) {
            $this->assertTrue(
                $buckets[$i] < 10500 
                    &&
                $buckets[$i] > 9500
            );
        }
    }
}