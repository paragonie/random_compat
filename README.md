# random_compat

[![Build Status](https://travis-ci.org/paragonie/random_compat.svg?branch=master)](https://travis-ci.org/paragonie/random_compat)

PHP 5.x polyfill for `random_bytes()` and `random_int()` created and maintained
by [Paragon Initiative Enterprises](https://paragonie.com).

Although this library *should* function in earlier versions of PHP, we will only
provide support for [supported PHP versions](https://secure.php.net/supported-versions.php).

## Important

**This library should be considered `EXPERIMENTAL`** until it has received sufficient
review from independent third party security experts. Please ask your favorite
hackers to hammer it for implementation errors and bugs.

For the background of this library, please refer to our blog post on 
[Generating Random Integers and Strings in PHP](https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php)

## Features

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

This project would not be anywhere near as excellent as it is today if it weren't for the contributions of the following individuals:

* [@CodesInChaos](https://github.com/CodesInChaos)
* [@lt (Leigh)](https://github.com/lt)
* [@MasonM (Mason Malone)](https://github.com/MasonM)
* [@narfbg (Andrey Andreev)](https://github.com/narfbg)
* [@oittaa](https://github.com/oittaa)
* [@SammyK (Sammy Kaye Powers)](https://github.com/SammyK)
* [@scottchiefbaker (Scott Baker)](https://github.com/scottchiefbaker)
* [@skyosev (Stoyan Kyosev)](https://github.com/skyosev)
