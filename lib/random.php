<?php
/**
 * Random_* Compatibility Library
 * for using the new PHP 7 random_* API in PHP 5 projects
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Paragon Initiative Enterprises
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

if (!defined('PHP_VERSION_ID')) {
    // This constant was introduced in PHP 5.2.7
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
if (PHP_VERSION_ID < 70000) {
    if (!defined('RANDOM_COMPAT_READ_BUFFER')) {
        define('RANDOM_COMPAT_READ_BUFFER', 8);
    }
    require_once "byte_safe_strings.php";
    require_once "error_polyfill.php";
    if (!function_exists('random_bytes')) {
        // The method we use depends on whether or not we're on a Windows server, so get this value early.
        $is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

        // Method 1:
        // openssl_random_psudo_bytes() exists in PHP 5.3.0, but before 5.3.4, there is possible blocking behavior on Windows. As of PHP 5.3.4, openssl_random_pseudo_bytes() and mycrypt_create_iv() do the exact same thing on Windows.
        if ($is_windows && extension_loaded('openssl') && PHP_VERSION_ID >= 50304) {
            require_once "random_bytes_openssl.php";
        }
        // Method 2:
        // On Windows PHP < 5.3.0, mcrypt_create_iv() calls rand().
        elseif ($is_windows && PHP_VERSION_ID > 50300 && extension_loaded('mcrypt')) {
            require_once "random_bytes_mcrypt.php";
        }
        // Method 3:
        // If none of the other methods are available, fall back on com_dotnet.
        elseif ($is_windows && extension_loaded('com_dotnet')) {
            require_once "random_bytes_com_dotnet.php";
        }
        // Method 4:
        // This is the fastest method available on Unix-like OSes, and uses OS randomness devices as sources of entropy.
        elseif (extension_loaded('openssl')) {
            require_once "random_bytes_openssl.php";
        }
        // Method 5:
        // Read directly from /dev/urandom if we can.
        elseif (!ini_get('open_basedir') && is_readable('/dev/urandom')) {
            require_once "random_bytes_dev_urandom.php";
        }
        // Method 6:
        // mcrypt_create_iv() with the MCRYPT_DEV_URANDOM flag does the same thing as Method 2, but is slower, possibly due to mcrypt_create_iv() doing some error checking that we're not doing. Regardless, this method will only be used if /dev/urandom is inaccessible for whatever reason.
        elseif (extension_loaded('mcrypt')) {
            require_once "random_bytes_mcrypt.php";
        }
        // Method 7:
        // We don't have any more options, so let's throw an exception right now and hope the developer won't let it fail silently.
        else {
            function random_bytes()
            {
                throw new Exception(
                    'There is no suitable CSPRNG installed on your system'
                );
            }
        }
    }
    if (!function_exists('random_int')) {
        require_once "random_int.php";
    }
}
