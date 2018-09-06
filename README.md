# random_compat

[![Build Status](https://travis-ci.org/paragonie/random_compat.svg?branch=master)](https://travis-ci.org/paragonie/random_compat)
[![Scrutinizer](https://scrutinizer-ci.com/g/paragonie/random_compat/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/paragonie/random_compat)
[![Latest Stable Version](https://poser.pugx.org/paragonie/random_compat/v/stable)](https://packagist.org/packages/paragonie/random_compat)
[![Latest Unstable Version](https://poser.pugx.org/paragonie/random_compat/v/unstable)](https://packagist.org/packages/paragonie/random_compat)
[![License](https://poser.pugx.org/paragonie/random_compat/license)](https://packagist.org/packages/paragonie/random_compat)
[![Downloads](https://img.shields.io/packagist/dt/paragonie/random_compat.svg)](https://packagist.org/packages/paragonie/random_compat)

PHP 5.x polyfill for `random_bytes()` and `random_int()` created and maintained
by [Paragon Initiative Enterprises](https://paragonie.com).

Although this library *should* function in earlier versions of PHP, we will only
consider issues relevant to [supported PHP versions](https://secure.php.net/supported-versions.php).
**If you are using an unsupported version of PHP, please upgrade as soon as possible.**

## Important

Although this library has been examined by some security experts in the PHP 
community, there will always be a chance that we overlooked something. Please 
ask your favorite trusted hackers to hammer it for implementation errors and
bugs before even thinking about deploying it in production.

**Do not use the master branch, use a [stable release](https://github.com/paragonie/random_compat/releases/latest).**

For the background of this library, please refer to our blog post on 
[Generating Random Integers and Strings in PHP](https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php).

### Usability Notice

If PHP cannot safely generate random data, this library will throw an `Exception`.
It will never fall back to insecure random data. If this keeps happening, upgrade
to a newer version of PHP immediately.

## Installing

**With [Composer](https://getcomposer.org):**

    # For libraries and frameworks that support PHP 5 but may be used by
    # other software that only supports PHP 7:
    composer require paragonie/random_compat:\>=2

    # For software that explicitly needs PHP 5 support:
    composer require paragonie/random_compat:\<9.99

**Signed PHP Archive:**

As of version 1.2.0, we also ship an ECDSA-signed PHP Archive with each stable 
release on Github.

1. Download [the `.phar`, `.phar.pubkey`, and `.phar.pubkey.asc`](https://github.com/paragonie/random_compat/releases/latest) files.
2. (**Recommended** but not required) Verify the PGP signature of `.phar.pubkey` 
   (contained within the `.asc` file) using the [PGP public key for Paragon Initiative Enterprises](https://paragonie.com/static/gpg-public-key.txt).
3. Extract both `.phar` and `.phar.pubkey` files to the same directory.
4. `require_once "/path/to/random_compat.phar";`
5. When a new version is released, you only need to replace the `.phar` file;
   the `.pubkey` will not change (unless our signing key is ever compromised).

**Manual Installation:**

1. Download [a stable release](https://github.com/paragonie/random_compat/releases/latest).
2. Extract the files into your project.
3. `require_once "/path/to/random_compat/lib/random.php";`

The entrypoint should be **`lib/random.php`** directly, not any of the other files in `/lib`.

## Usage

This library exposes the [CSPRNG functions added in PHP 7](https://secure.php.net/manual/en/ref.csprng.php)
for use in PHP 5 projects. Their behavior should be identical.

### Generate a string of random bytes

```php
try {
    $string = random_bytes(32);
} catch (TypeError $e) {
    // Well, it's an integer, so this IS unexpected.
    die("An unexpected error has occurred"); 
} catch (Error $e) {
    // This is also unexpected because 32 is a reasonable integer.
    die("An unexpected error has occurred");
} catch (Exception $e) {
    // If you get this message, the CSPRNG failed hard.
    die("Could not generate a random string. Is our OS secure?");
}

var_dump(bin2hex($string));
// string(64) "5787c41ae124b3b9363b7825104f8bc8cf27c4c3036573e5f0d4a91ad2eeac6f"
```

### Generate a random integer between two given integers (inclusive)

```php
try {
    $int = random_int(0, 255);
} catch (TypeError $e) {
    // Well, it's an integer, so this IS unexpected.
    die("An unexpected error has occurred"); 
} catch (Error $e) {
    // This is also unexpected because 0 and 255 are both reasonable integers.
    die("An unexpected error has occurred");
} catch (Exception $e) {
    // If you get this message, the CSPRNG failed hard.
    die("Could not generate a random int. Is our OS secure?");
}

var_dump($int);
// int(47)
```

### Exception handling

When handling exceptions and errors you must account for differences between
PHP 5 and PHP7.

The differences:

* Catching `Error` works, so long as it is caught before `Exception`.
* Catching `Exception` has different behavior, without previously catching `Error`.
* There is *no* portable way to catch all errors/exceptions.

#### Our recommendation

**Always** catch `Error` before `Exception`.

#### Example

```php
try {
    return random_int(1, $userInput);
} catch (TypeError $e) {
    // This is okay, so long as `Error` is caught before `Exception`.
    throw new Exception('Please enter a number!');
} catch (Error $e) {
    // This is required, if you do not need to do anything just rethrow.
    throw $e;
} catch (Exception $e) {
    // This is optional and maybe omitted if you do not want to handle errors
    // during generation.
    throw new InternalServerErrorException(
        'Oops, our server is bust and cannot generate any random data.',
        500,
        $e
    );
}
```

### Troubleshooting

#### Exception: "Could not gather sufficient random data"

If an Exception is thrown, then your operating system is not secure.

1. If you're on Windows, make sure you enable mcrypt.
2. If you're on any other OS, make sure `/dev/urandom` is readable.
   * FreeBSD jails need to expose `/dev/urandom` from the host OS
   * If you use `open_basedir`, make sure `/dev/urandom` is allowed

This library does not (and will not accept any patches to) fall back to
an insecure random number generator.

#### Version Conflict with [Other PHP Project]

If you're using a project that has a line like this in its composer.json

    "require" {
        ...
        "paragonie/random_compat": "~1.1",
        ...
    }

...and then you try to add random_compat 2 (or another library that explicitly
requires random_compat 2, such as [this secure PHP encryption library](https://github.com/defuse/php-encryption)),
you will get a version conflict.

The solution is to get the project to update its requirement string to allow
version 2 and above to be used instead of hard-locking users to version 1.

```diff
"require" {
    ...
-    "paragonie/random_compat": "~1.1",
+    "paragonie/random_compat": ">=1",
    ...
}
```

#### Version 9.99.99

**Note**: There is a special version called `9.99.99` which makes this
library do nothing, but is only installable on PHP 7.

If you're writing software (e.g. a library) that supports PHP 5, but may
be used by software that doesn't, you'll want to allow `9.99.99` to be
installed. The above diff is what you want.

Conversely, if you're writing software that (in and of itself) supports
PHP 5, you do not want 9.99.99 to be installed, so you'll want to make
this change instead:

```diff
"require" {
    ...
-    "paragonie/random_compat": "~1.1",
+    "paragonie/random_compat": ">=1 <9.99",
    ...
}
```

To avoid installing "empty" version `9.99.99` you can add `replace` section
in your root `composer.json`:

    "replace": {
        "paragonie/random_compat": "9.99.99"
    },

#### Manifest Read Length Error

If you're using the PHP Archive (Phar) approach rather than Composer, and
you are getting an error message to the effect of "manifest read length
was `{int1}` should be `{int2}`", the Phar extension may not be enabled.

See [this comment](https://github.com/paragonie/random_compat/issues/134#issuecomment-365696289)
for specific guidance on how to fix this issue.

## Contributors

This project would not be anywhere near as excellent as it is today if it 
weren't for the contributions of the following individuals:

* [@AndrewCarterUK (Andrew Carter)](https://github.com/AndrewCarterUK)
* [@asgrim (James Titcumb)](https://github.com/asgrim)
* [@bcremer (Benjamin Cremer)](https://github.com/bcremer)
* [@chriscct7 (Chris Christoff)](https://github.com/chriscct7)
* [@CodesInChaos (Christian Winnerlein)](https://github.com/CodesInChaos)
* [@ConnorVG (Connor S. Parks)](https://github.com/ConnorVG)
* [@cs278 (Chris Smith)](https://github.com/cs278)
* [@cweagans (Cameron Eagans)](https://github.com/cweagans)
* [@dd32 (Dion Hulse)](https://github.com/dd32)
* [@geggleto (Glenn Eggleton)](https://github.com/geggleto)
* [@glensc (Elan Ruusamäe)](https://github.com/glensc)
* [@GrahamCampbell (Graham Campbell)](https://github.com/GrahamCampbell)
* [@ircmaxell (Anthony Ferrara)](https://github.com/ircmaxell)
* [@jdevalk (Joost de Valk)](https://github.com/jdevalk)
* [@jedisct1 (Frank Denis)](https://github.com/jedisct1)
* [@juliangut (Julián Gutiérrez)](https://github.com/juliangut)
* [@kelunik (Niklas Keller)](https://github.com/kelunik)
* [@lt (Leigh)](https://github.com/lt)
* [@MasonM (Mason Malone)](https://github.com/MasonM)
* [@menkaff (Mehran NikNafs)](https://github.com/menkaff)
* [@mmeyer2k (Michael M)](https://github.com/mmeyer2k)
* [@narfbg (Andrey Andreev)](https://github.com/narfbg)
* [@nicolas-grekas (Nicolas Grekas)](https://github.com/nicolas-grekas)
* [@ocean90 (Dominik Schilling)](https://github.com/ocean90)
* [@oittaa](https://github.com/oittaa)
* [@oucil (Kevin Farley)](https://github.com/oucil)
* [@philios33 (Phil Nicholls)](https://github.com/philios33)
* [@redragonx (Stephen Chavez)](https://github.com/redragonx)
* [@relaxnow (Boy Baukema)](https://github.com/relaxnow)
* [@rchouinard (Ryan Chouinard)](https://github.com/rchouinard)
* [@rugk](https://github.com/rugk)
* [@SammyK (Sammy Kaye Powers)](https://github.com/SammyK)
* [@scottchiefbaker (Scott Baker)](https://github.com/scottchiefbaker)
* [@skyosev (Stoyan Kyosev)](https://github.com/skyosev)
* [@sthen (Stuart Henderseon)](https://github.com/sthen)
* [@stof (Christophe Coevoet)](https://github.com/stof)
* [@teohhanhui (Teoh Han Hui)](https://github.com/teohhanhui)
* [@tom-- (Tom Worster)](https://github.com/tom--)
* [@tsyr2ko](https://github.com/tsyr2ko)
* [@trowski (Aaron Piotrowski)](https://github.com/trowski)
* [@twistor (Chris Lepannen)](https://github.com/twistor)
* [@vinkla (Vincent Klaiber)](https://github.com/vinkla)
* [@voku (Lars Moelleken)](https://github.com/voku)
* [@xabbuh (Christian Flothmann)](https://github.com/xabbuh)
