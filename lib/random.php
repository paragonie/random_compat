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
    /**
     * @const RANDOM_COMPAT_ALLOW_OPENSSL Set true to allow use of OpenSSL's random generator
     */
    if (!defined('RANDOM_COMPAT_ALLOW_OPENSSL')) {
        define('RANDOM_COMPAT_ALLOW_OPENSSL', false);
    }
    /**
     * RandomCompatOpenSslWarning is thrown by random_bytes() and random_int() when PHP's only
     * available source of randomness is OpenSSL and its use is disallowed by configuration.
     *
     * It is not always safe to use OpenSSL's random generator. It might not be properly seeded
     * and is more vulnerable than operating system generators. random_compat is therefore
     * configured to not use OpenSSL by default.
     *
     * To resolve this exception you can
     * - reconfigure PHP with the Mcrypt extension
     * - move the application to another PHP environment with Mcrypt or on Linux, BSD or similar OS
     * - upgrade to PHP 7
     *
     * Alternatively you may set RANDOM_COMPAT_ALLOW_OPENSSL true if you are confident OpenSSL's
     * random generator is safe for your application. In PHP it is not normally possible to know
     * when OpenSSL's RNG is properly seeded and safe to use in cryptography tasks, such as generating
     * keys and initialization vectors, or for other things that depend on randomness, such as
     * one-time passwords, authentication IDs and shared secrets. Since there is no general way
     * to judge when OpenSSL's generator is tolerable in a given PHP application, if you have any
     * doubt then the safe choice is to not use it.
     */
    class RandomCompatOpenSslWarning extends \Exception {
        protected $message = 'random_bytes()/random_int() failed to access a reliable source of '
        . 'randomness and is not allowed to use OpenSSL. See RandomCompatOpenSslWarning documentation.';
    }
    require_once "byte_safe_strings.php";
    require_once "error_polyfill.php";
    if (!function_exists('random_bytes')) {
        /**
         * PHP 5.2.0 - 5.6.x way to implement random_bytes()
         * 
         * We use conditional statements here to define the function in accordance
         * to the operating environment. It's a micro-optimization.
         * 
         * In order of preference:
         *   1. Use libsodium if available.
         *   2. fread() /dev/urandom if available (never on Windows)
         *   3. mcrypt_create_iv($bytes, MCRYPT_CREATE_IV)
         *   4. COM('CAPICOM.Utilities.1')->GetRandom()
         *   5. openssl_random_pseudo_bytes() (absolute last resort)
         * 
         * See ERRATA.md for our reasoning behind this particular order
         */
        if (extension_loaded('libsodium')) {
            // See random_bytes_libsodium.php
            require_once "random_bytes_libsodium.php";
        } elseif (DIRECTORY_SEPARATOR === '/' && @is_readable('/dev/urandom')) {
            // DIRECTORY_SEPARATOR === '/' on Unix-like OSes -- this is a fast
            // way to exclude Windows.
            // 
            // Error suppression on is_readable() in case of an open_basedir or 
            // safe_mode failure. All we care about is whether or not we can 
            // read it at this point. If the PHP environment is going to panic 
            // over trying to see if the file can be read in the first place,
            // that is not helpful to us here.
            
            // See random_bytes_dev_urandom.php
            require_once "random_bytes_dev_urandom.php";
        } elseif (PHP_VERSION_ID >= 50307 && extension_loaded('mcrypt')) {
            // See random_bytes_mcrypt.php
            require_once "random_bytes_mcrypt.php";
        } elseif (extension_loaded('com_dotnet')) {
            // See random_bytes_com_dotnet.php
            require_once "random_bytes_com_dotnet.php";
        } elseif (extension_loaded('openssl') && PHP_VERSION_ID >= 50300) {
            if (!RANDOM_COMPAT_ALLOW_OPENSSL) {
                throw new \RandomCompatOpenSslWarning;
            }
            // See random_bytes_openssl.php
            require_once "random_bytes_openssl.php";
        } else {
            /**
             * We don't have any more options, so let's throw an exception right now
             * and hope the developer won't let it fail silently.
             */
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
