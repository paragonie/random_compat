<?php
/*
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

class Paragonie_RandomBytes extends Paragonie_RandomAdapter
{
    /**
     * @param int $bytes
     * 
     * @throws Exception
     * 
     * @return string
     */
    public static function random_bytes($bytes)
    {
        is_int($bytes) or $bytes = Paragonie_Util_Intval::intval($bytes, __FUNCTION__, 1);

        if ($bytes < 1) {
            throw new Error(
                'Length must be greater than 0'
            );
        }

        $buf = parent::do_random_bytes($bytes);

        if (!isset($buf[$bytes - 1])) {
            throw new Exception(
                'Could not gather sufficient random data'
            );
        }
        if (isset($buf[$bytes])) {
            $buf = Paragonie_Util_Binary::substr($buf, 0, $bytes);
        }
        return $buf;
    }
}
