<?php
namespace {
    if (!function_exists('random_bytes')) {
        /**
         * PHP 5.2.0 - 5.6.x way to implement random_bytes()
         * 
         * @param int $bytes
         * @return string
         */
        function random_bytes($bytes)
        {
            if (!\is_int($bytes) || $bytes < 1) {
                 throw new \InvalidArgumentException('random_bytes() expects a positive integer');
            }
            $buf = '';
            if (\function_exists('openssl_random_pseudo_bytes')) {
                $secure = true;
                $buf = \openssl_random_pseudo_bytes($bytes, $secure);
                if ($buf !== false && $secure) {
                    return $buf;
                }
            }
            // See PHP bug #55169 for why 5.3.7 is required
            if (
                \function_exists('mcrypt_create_iv') &&
                \version_compare(PHP_VERSION, '5.3.7') >= 0
            ) {
                $buf = \mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
                if ($buf !== false) {
                    return $buf;
                }
            }

            /**
             * Use /dev/urandom for random numbers
             * 
             * @ref http://sockpuppet.org/blog/2014/02/25/safely-generate-random-numbers
             */
            if (\is_readable('/dev/urandom')) {
                $fp = \fopen('/dev/urandom', 'rb');
                if ($fp !== false) {
                    $streamset = \stream_set_read_buffer($fp, 0);
                    if ($streamset === 0) {
                        $remaining = $bytes;
                        do {
                            $read = \fread($fp, $remaining); 
                            if ($read === false) {
                                // We cannot safely read from urandom.
                                $buf = false;
                                break;
                            }
                            // Decrease the number of bytes returned from remaining
                            $remaining -= \ParagonIE\RandomCompat\_strlen($read);
                            $buf .= $read;
                        } while ($remaining > 0);
                        if ($buf !== false) {
                            return $buf;
                        }
                    }
                }
            }
            /**
             * Windows with PHP < 5.3.0 will not have the function
             * openssl_random_pseudo_bytes() available, so let's use
             * CAPICOM to work around this deficiency.
             */
             if (\class_exists('COM', false)) {
                 try {
                     if ($buf === false) {
                         $buf = ''; // Make it a string, not false
                     }
                    $util = new \COM('CAPICOM.Utilities.1');
                    $execs = 0;
                    /**
                     * Let's not let it loop forever. If we run N times and fail to
                     * get N bytes of random data, then CAPICOM has failed us.
                     */
                    do {
                        $buf .= base64_decode($util->GetRandom($bytes, 0));
                        if (\ParagonIE\RandomCompat\strlen($buf) >= $bytes) {
                            return \ParagonIE\RandomCompat\_substr($buf, 0, $bytes);
                        }
                        ++$execs; 
                    } while ($execs < $bytes);
                } catch (\Exception $e) {
                    unset($e); // Let's not let CAPICOM errors kill our app 
                }
            }
            /**
             * We have reached the point of no return. Throw an exception.
             */
            throw new \Exception('PHP failed to generate random data.');
        }
    }
    
    if (!function_exists('random_int')) {
        function random_int($min, $max)
        {
            if (!\is_int($min) || !\is_int($max)) {
                 throw new \InvalidArgumentException('random_int() expects two positive integers');
            }
            if ($min >= $max) {
                 throw new \InvalidArgumentException('$min must be less than $max');
            }
            $range = $max - $min;
            // Test for integer overflow:
            if (!\is_int($range)) {
                 throw new \InvalidArgumentException('Integer overflow');
            }
            // Do we have a meaningful range?
            if ($range < 1) {
                return $min;
            }

            // Initialize variables to 0
            $bits = $bytes = $mask = 0;

            $tmp = $range;
            /**
             * We want to store:
             * $bytes => the number of random bytes we need
             * $mask => an integer bitmask (for use with the &) operator
             *          so we can minimize the number of discards
             */
            while ($tmp > 0) {
                if ($bits % 8 === 0) {
                   ++$bytes;
                }
                ++$bits;
                $tmp >>= 1;
                $mask = $mask << 1 | 1;
            }

            /**
             * Now that we have our parameters set up, let's begin generating
             * random integers until one falls within $range
             */
            do {
                $rval = random_bytes($bytes);
                if ($rval === false) {
                    throw new \Exception('Random number generator failure');
                }

                /**
                 * Let's turn $rval (random bytes) into an integer
                 * 
                 * This uses bitwise operators (<< and |) to build an integer
                 * out of the values extracted from ord()
                 * 
                 * Example: [9F] | [6D] | [32] | [0C] =>
                 *   159 + 27904 + 3276800 + 201326592 =>
                 *   204631455
                 */
                $val = 0;
                for ($i = 0; $i < $bytes; ++$i) {
                    $val |= (ord($rval[$i]) << ($i * 8));
                }

                // Apply mask
                $val &= $mask;

                // If $val is larger than the maximum acceptable number for
                // $min and $max, we discard and try again.
            } while ($val > $range);
            return (int) ($min + $val) & PHP_INT_MAX;
        }
    }
}

namespace ParagonIE\RandomCompat {
    if (!function_exists('\\ParagonIE\\RandomCompat\\_strlen')) {
        function _strlen($binary_string)
        {
            static $exists = null;
            if ($exists === null) {
                $exists = \function_exists('mb_strlen');
            }
            if ($exists) {
                return \mb_strlen($binary_string, '8bit');
            }
            return \strlen($binary_string);
        }
    }
    if (!function_exists('\\ParagonIE\\RandomCompat\\_substr')) {
        function _substr($binary_string, $start, $length = null)
        {
            static $exists = null;
            if ($exists === null) {
                $exists = \function_exists('mb_substr');
            }
            if ($exists) {
                return \mb_substr($binary_string, $start, $length, '8bit');
            }
            return \substr($binary_string, $start, $length);
        }
    }
}