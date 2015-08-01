# random_compat

[![Build Status](https://travis-ci.org/paragonie/random_compat.svg?branch=master)](https://travis-ci.org/paragonie/random_compat)

PHP 5.x polyfill for `random_bytes()` and `random_int()` created and maintained
by [Paragon Initiative Enterprises](https://paragonie.com).

Although this library *should* function in earlier versions of PHP, we will only
consider issues relevant to [supported PHP versions](https://secure.php.net/supported-versions.php).
**Please upgrade as soon as possible.**

## Important

**This library should be considered `EXPERIMENTAL`** until it has received sufficient
review from independent third party security experts. Please ask your favorite
hackers to hammer it for implementation errors and bugs.

For the background of this library, please refer to our blog post on 
[Generating Random Integers and Strings in PHP](https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php).

### Usability Notice

If PHP cannot safely generate random data, this library will throw an `Exception`
when you include it, even if it's unused. It will not fall back to insecure
random data.

## Features

This library exposes the [CSPRNG functions added in PHP 7](https://secure.php.net/manual/en/ref.csprng.php)
for use in PHP 5 projects. Their behavior should be identical.

### Generate a string of random bytes

```php
$string = random_bytes(32);

var_dump(bin2hex($string));
// string(64) "5787c41ae124b3b9363b7825104f8bc8cf27c4c3036573e5f0d4a91ad2eeac6f"
```

### Generate a random integer between two given integers (inclusive)

```php
$int = random_int(0,255);
var_dump($int);
// int(47)
```

## Contributors

This project would not be anywhere near as excellent as it is today if it 
weren't for the contributions of the following individuals:

* [@CodesInChaos (Christian Winnerlein)](https://github.com/CodesInChaos)
* [@kelunik (Niklas Keller)](https://github.com/kelunik)
* [@lt (Leigh)](https://github.com/lt)
* [@MasonM (Mason Malone)](https://github.com/MasonM)
* [@jedisct1 (Frank Denis)](https://github.com/jedisct1)
* [@narfbg (Andrey Andreev)](https://github.com/narfbg)
* [@oittaa](https://github.com/oittaa)
* [@redragonx (Stephen Chavez)](https://github.com/redragonx)
* [@SammyK (Sammy Kaye Powers)](https://github.com/SammyK)
* [@scottchiefbaker (Scott Baker)](https://github.com/scottchiefbaker)
* [@skyosev (Stoyan Kyosev)](https://github.com/skyosev)
* [@tsyr2ko](https://github.com/tsyr2ko)
