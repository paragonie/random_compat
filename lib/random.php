<?php

if (!function_exists('random_bytes')) {
    /**
     * PHP 5.2.0 - 5.6.x way to implement random_bytes()
     * 
     * We use conditional statements here to define the function in accordance
     * to the operating environment. It's a micro-optimization.
     * 
     * In order of preference:
     *   1. fread() /dev/arandom if available
     *   2. fread() /dev/urandom if available
     *   3. mcrypt_create_iv($bytes, MCRYPT_CREATE_IV)
     *   4. COM('CAPICOM.Utilities.1')->GetRandom()
     *   5. openssl_random_pseudo_bytes()
     * 
     * See ERRATA.md for our reasoning behind this particular order
     */
     if (
        !ini_get('open_basedir') && 
        (
            is_readable('/dev/arandom') || is_readable('/dev/urandom')
        )
    ) {
        /**
         * Unless open_basedir is enabled, use /dev/arandom or /dev/urandom for
         * random numbers in accordance with best practices
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
                /**
                 * If we don't set the stream's read buffer to 0, PHP will
                 * internally buffer 8192 bytes, which can waste entropy
                 * 
                 * stream_set_read_buffer returns 0 on success
                 */
                $streamset = stream_set_read_buffer($fp, 0);
                if ($streamset === 0) {
                    $remaining = $bytes;
                    $buf = '';
                    /**
                     * We use fread() in a loop to protect against partial reads
                     */
                    do {
                        $read = fread($fp, $remaining); 
                        if ($read === false) {
                            /**
                             * We cannot safely read from the file. Exit the
                             * do-while loop and trigger the exception condition
                             */
                            $buf = false;
                            break;
                        }
                        /**
                         * Decrease the number of bytes returned from remaining
                         */
                        $remaining -= RandomCompat_strlen($read);
                        $buf .= $read;
                    } while ($remaining > 0);
                    
                    /**
                     * Is our result valid?
                     */
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
    } elseif (function_exists('mcrypt_create_iv') && version_compare(PHP_VERSION, '5.3.7') >= 0) {
        /**
         * Powered by ext/mcrypt (and thankfully NOT libmcrypt)
         * 
         * @ref https://bugs.php.net/bug.php?id=55169
         * @ref https://github.com/php/php-src/blob/c568ffe5171d942161fc8dda066bce844bdef676/ext/mcrypt/mcrypt.c#L1321-L1386
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
            
            $buf = mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
            if ($buf !== false) {
                if (RandomCompat_strlen($buf) === $bytes) {
                    /**
                     * Return our random entropy buffer here:
                     */
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
                    /**
                     * Return our random entropy buffer here:
                     */
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
            /**
             * $secure is passed by reference. If it's set to false, fail. Note
             * that this will only return false if this function fails to return
             * any data.
             * 
             * @ref https://github.com/paragonie/random_compat/issues/6#issuecomment-119564973
             */
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
         * and hope the developer won't let it fail silently.
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
        /**
         * Type and input logic checks
         */
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
         * 
         * We want to store:
         * $bytes => the number of random bytes we need
         * $mask => an integer bitmask (for use with the &) operator
         *          so we can minimize the number of discards
         */
        $attempts = $bits = $bytes = $mask = $valueShift = 0;

        /**
         * At this point, $range is a positive number greater than 0. It might
         * overflow, however, if $max - $min > PHP_INT_MAX. PHP will cast it to
         * a float and we will lose some precision.
         */
        $range = $max - $min;

        /**
         * Test for integer overflow:
         */
        if (!is_int($range)) {
            /**
             * Still safely calculate wider ranges.
             * Provided by @CodesInChaos, @oittaa
             * 
             * @ref https://gist.github.com/CodesInChaos/03f9ea0b58e8b2b8d435
             * 
             * We use ~0 as a mask in this case becuase it genreates all 1s
             * 
             * @ref https://eval.in/400356 (32-bit)
             * @ref http://3v4l.org/XX9r5  (64-bit)
             */
            $bytes = PHP_INT_SIZE;
            $mask = ~0;
        } else {
            /**
             * $bits is effectively ceil(log($range, 2)) without dealing with 
             * type juggling
             */
            while ($range > 0) {
                if ($bits % 8 === 0) {
                   ++$bytes;
                }
                ++$bits;
                $range >>= 1;
                $mask = $mask << 1 | 1;
            }
            $valueShift = $min;
        }

        /**
         * Now that we have our parameters set up, let's begin generating
         * random integers until one falls between $min and $max
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
            
            /**
             * Let's grab the necessary number of random bytes
             */
            $randomByteString = random_bytes($bytes);
            if ($randomByteString === false) {
                throw new Exception(
                    'Random number generator failure'
                );
            }

            /**
             * Let's turn $randomByteString into an integer
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
                $val |= ord($randomByteString[$i]) << ($i * 8);
            }

            /**
             * Apply mask
             */
            $val &= $mask;
            $val += $valueShift;

            ++$attempts;
            /**
             * If $val overflows to a floating point number,
             * ... or is larger than $max,
             * ... or smaller than $int,
             * then try again.
             */
        } while (!is_int($val) || $val > $max || $val < $min);
        return (int) $val;
    }
}

if (!function_exists('RandomCompat_strlen')) {
    if (function_exists('mb_strlen')) {
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
         * This version just uses the default substr()
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
