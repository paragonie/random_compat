<?php

if (!function_exists('random_bytes')) {
    /**
     * PHP 5.2.0 - 5.6.x way to implement random_bytes()
     */
    if (function_exists('mcrypt_create_iv') && version_compare(PHP_VERSION, '5.3.7') >= 0) {
        /**
         * Powered by ext/mcrypt
         * 
         * @param int $bytes
         * @return string
         */
        function random_bytes($bytes)
        {
            if (!is_int($bytes)) {
                throw new Exception(
                    'Length must be an integer'
                );
            }
            if ($bytes < 1) {
                throw new Exception(
                    'Length must be greater than 0'
                );
            }
            // See PHP bug #55169 for why 5.3.7 is required
            $buf = mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
            if ($buf !== false) {
                if (RandomCompat_strlen($buf) === $bytes) {
                    return $buf;
                }
            }
            /**
             * If we reach here, PHP has failed us.
             */
            throw new Exception(
                'PHP failed to generate random data.'
            );
        }
    } elseif ((is_readable('/dev/arandom') || is_readable('/dev/urandom')) && !ini_get('open_basedir')) {
        /**
         * Use /dev/arandom or /dev/urandom for random numbers
         * 
         * @ref http://sockpuppet.org/blog/2014/02/25/safely-generate-random-numbers
         * 
         * @param int $bytes
         * @return string
         */
        function random_bytes($bytes)
        {
            static $fp = null;
            if ($fp === null) {
                if (is_readable('/dev/arandom')) {
                    $fp = fopen('/dev/arandom', 'rb');
                } else {
                    $fp = fopen('/dev/urandom', 'rb');
                }
            }
            if ($fp !== false) {
                $streamset = stream_set_read_buffer($fp, 0);
                if ($streamset === 0) {
                    $remaining = $bytes;
                    $buf = '';
                    do {
                        $read = fread($fp, $remaining); 
                        if ($read === false) {
                            // We cannot safely read from urandom.
                            $buf = false;
                            break;
                        }
                        // Decrease the number of bytes returned from remaining
                        $remaining -= RandomCompat_strlen($read);
                        $buf .= $read;
                    } while ($remaining > 0);
                    if ($buf !== false) {
                        if (RandomCompat_strlen($buf) === $bytes) {
                            /**
                             * Return our random entropy buffer here:
                             */
                            return $buf;
                        }
                    }
                }
            }
            /**
             * If we reach here, PHP has failed us.
             */
            throw new Exception(
                'PHP failed to generate random data.'
            );
        }
    } elseif (extension_loaded('com_dotnet')) {
        /**
         * Windows with PHP < 5.3.0 will not have the function
         * openssl_random_pseudo_bytes() available, so let's use
         * CAPICOM to work around this deficiency.
         * 
         * @param int $bytes
         * @return string
         */
        function random_bytes($bytes)
        {
            $buf = '';
            $util = new COM('CAPICOM.Utilities.1');
            $execCount = 0;
            /**
             * Let's not let it loop forever. If we run N times and fail to
             * get N bytes of random data, then CAPICOM has failed us.
             */
            do {
                $buf .= base64_decode($util->GetRandom($bytes, 0));
                if (RandomCompat_strlen($buf) >= $bytes) {
                    return RandomCompat_substr($buf, 0, $bytes);
                }
                ++$execCount; 
            } while ($execCount < $bytes);
            /**
             * If we reach here, PHP has failed us.
             */
            throw new Exception(
                'PHP failed to generate random data.'
            );
        }
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        /**
         * Since openssl_random_pseudo_bytes() uses openssl's 
         * RAND_pseudo_bytes() API, which has been marked as deprecated by the
         * OpenSSL team, this is our last resort before failure.
         * 
         * @ref https://www.openssl.org/docs/crypto/RAND_bytes.html
         * 
         * @param int $bytes
         * @return string
         */
        function random_bytes($bytes)
        {
            $secure = true;
            $buf = openssl_random_pseudo_bytes($bytes, $secure);
            if ($buf !== false && $secure) {
                if (RandomCompat_strlen($buf) === $bytes) {
                    return $buf;
                }
            }
            /**
             * If we reach here, PHP has failed us.
             */
            throw new Exception(
                'PHP failed to generate random data.'
            );
        }
    } else {
        /**
         * We don't have any more options, so let's throw an exception right now
         */
        throw new Exception(
            'There is no suitable CSPRNG installed on your system'
        );
    }
}

if (!function_exists('random_int')) {
    /**
     * Fetch a random integer between $min and $max inclusive
     * 
     * @param int $min
     * @param int $max
     * 
     * @return int
     */
    function random_int($min, $max)
    {
        if (!is_int($min)) {
            throw new Exception(
                'random_int(): $min must be an integer'
            );
        }
        if (!is_int($max)) {
            throw new Exception(
                'random_int(): $max must be an integer'
            );
        }
        if ($min > $max) {
            throw new Exception(
                'Minimum value must be less than or equal to the maximum value'
            );
        }
        if ($max === $min) {
            return $min;
        }
        
        /**
         * Initialize variables to 0
         */
        $attempts = $bits = $bytes = $mask = 0;

        $range = $max - $min + 1;

        /**
         * Test for integer overflow:
         */
        if (!is_int($range)) {
            /**
             * Still safely calculate wider ranges.
             * Provided by @CodesInChaos
             * 
             * @ref https://gist.github.com/CodesInChaos/03f9ea0b58e8b2b8d435
             * 
             * The rejection probability is at most 0.5, so this corresponds
             * to a failure probability of 2^-128 for a working RNG
             */
            $bytes = PHP_INT_SIZE;
        } else {
            /**
             * We incremented $range earlier to test for overflows
             */
            --$range;
        }
        
        $tmp = $range;
        while ($tmp > 0) {
            /**
             * We want to store:
             * $bytes => the number of random bytes we need
             * $mask => an integer bitmask (for use with the &) operator
             *          so we can minimize the number of discards
             * 
             * $bits is effectively ceil(log($range, 2)) without dealing with 
             * type juggling
             */
            while ($range > 0) {
                if ($bits % 8 === 0) {
                   ++$bytes;
                }
                ++$bits;
                $tmp >>= 1;
                $mask = $mask << 1 | 1;
                $valueShift = $min;
            }
        }

        /**
         * Now that we have our parameters set up, let's begin generating
         * random integers until one falls within $range
         */
        do {
            /**
             * The rejection probability is at most 0.5, so this corresponds
             * to a failure probability of 2^-128 for a working RNG
             */
            if ($attempts > 128) {
                throw new Exception(
                    'random_int: RNG is broken - too many rejections'
                );
            }
            $rval = random_bytes($bytes);
            if ($rval === false) {
                throw new Exception(
                    'Random number generator failure'
                );
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

            /**
             * Apply mask
             */
            $val += $valueShift;

            ++$attempts;
            /**
             * If $val is larger than the maximum acceptable number for
             * $min and $max, we discard and try again.
             */
        } while (!is_int($val) || $val > $max || $val < $min);
        return (int) $val;
    }
}

if (!function_exists('RandomCompat_strlen')) {
    if (function_exists('mb_substr')) {
        /**
         * strlen() implementation that isn't brittle to mbstring.func_overload
         * 
         * This version uses mb_strlen() in '8bit' mode to treat strings as raw
         * binary rather than UTF-8, ISO-8859-1, etc
         * 
         * @param string $binary_string
         * 
         * @return int
         */
        function RandomCompat_strlen($binary_string)
        {
            if (!is_string($binary_string)) {
                throw new InvalidArgumentException(
                    'RandomCompat_strlen() expects a string'
                );
            }
            return mb_strlen($binary_string, '8bit');
        }
    } else {
        /**
         * strlen() implementation that isn't brittle to mbstring.func_overload
         * 
         * This version just used the default strlen()
         * 
         * @param string $binary_string
         * 
         * @return int
         */
        function RandomCompat_strlen($binary_string)
        {
            if (!is_string($binary_string)) {
                throw new InvalidArgumentException(
                    'RandomCompat_strlen() expects a string'
                );
            }
            return strlen($binary_string);
        }
    }
}

if (!function_exists('RandomCompat_substr')) {
    if (function_exists('mb_substr')) {
        /**
         * substr() implementation that isn't brittle to mbstring.func_overload
         * 
         * This version uses mb_substr() in '8bit' mode to treat strings as raw
         * binary rather than UTF-8, ISO-8859-1, etc
         * 
         * @param string $binary_string
         * @param int $start
         * @param int $length (optional)
         * 
         * @return string
         */
        function RandomCompat_substr($binary_string, $start, $length = null)
        {
            if (!is_string($binary_string)) {
                throw new InvalidArgumentException(
                    'RandomCompat_substr(): First argument should be a string'
                );
            }
            if (!is_int($start)) {
                throw new InvalidArgumentException(
                    'RandomCompat_substr(): Second argument should be an integer'
                );
            }
            if ($length === null) {
                /**
                 * mb_substr($str, 0, NULL, '8bit') returns an empty string on
                 * PHP 5.3, so we have to find the length ourselves.
                 */
                $length = RandomCompat_strlen($length) - $start;
            } elseif (!is_int($length)) {
                throw new InvalidArgumentException(
                    'RandomCompat_substr(): Third argument should be an integer, or omitted'
                );
            }
            return mb_substr($binary_string, $start, $length, '8bit');
        }
    } else {
        /**
         * substr() implementation that isn't brittle to mbstring.func_overload
         * 
         * This version just used the default substr()
         * 
         * @param string $binary_string
         * @param int $start
         * @param int $length (optional)
         * 
         * @return string
         */
        function RandomCompat_substr($binary_string, $start, $length = null)
        {
            if (!is_string($binary_string)) {
                throw new InvalidArgumentException(
                    'RandomCompat_substr(): First argument should be a string'
                );
            }
            if (!is_int($start)) {
                throw new InvalidArgumentException(
                    'RandomCompat_substr(): Second argument should be an integer'
                );
            }
            if ($length !== null) {
                if (!is_int($length)) {
                    throw new InvalidArgumentException(
                        'RandomCompat_substr(): Third argument should be an integer, or omitted'
                    );
                }
                return substr($binary_string, $start, $length);
            }
            return substr($binary_string, $start);
        }
    }
}
