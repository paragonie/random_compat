#!/usr/bin/env bash

if [ "$1" == 'full' ]; then
    fulltest=1
elif [ "$1" == 'each' ]; then
    testeach=1
else
    fulltest=0
fi
origdir=`pwd`
cdir=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
cd $origdir
parentdir="$(dirname $cdir)"

clean=0 # Clean up?

gpg --fingerprint D8406D0D82947747293778314AA394086372C20A
if [ $? -ne 0 ]; then
    echo -e "\033[33mDownloading PGP Public Key...\033[0m"
    gpg --recv-keys D8406D0D82947747293778314AA394086372C20A
    # Sebastian Bergmann <sb@sebastian-bergmann.de>
    gpg --fingerprint D8406D0D82947747293778314AA394086372C20A
    if [ $? -ne 0 ]; then
        echo -e "\033[31mCould not download PGP public key for verification\033[0m"
        exit
    fi
fi

if [ "$clean" -eq 1 ]; then
    # Let's clean them up, if they exist
    if [ -f phpunit.phar ]; then
        rm -f phpunit.phar
    fi
    if [ -f phpunit.phar.asc ]; then
        rm -f phpunit.phar.asc
    fi
fi

PHP_VERSION=$(php -r "echo PHP_VERSION_ID;")

# Let's grab the latest release and its signature
if [ ! -f phpunit.phar ]; then
    if [[ $PHP_VERSION -ge 50600 ]]; then
        wget https://phar.phpunit.de/phpunit.phar
    else
        wget -O phpunit.phar https://phar.phpunit.de/phpunit-old.phar
    fi
fi
if [ ! -f phpunit.phar.asc ]; then
    if [[ $PHP_VERSION -ge 50600 ]]; then
        wget https://phar.phpunit.de/phpunit.phar.asc
    else
        wget -O phpunit.phar.asc https://phar.phpunit.de/phpunit-old.phar.asc
    fi
fi

# Verify before running
gpg --verify phpunit.phar.asc phpunit.phar
if [ $? -eq 0 ]; then
    echo
    echo -e "\033[33mBegin Unit Testing\033[0m"
    # Run the testing suite
    echo "Basic test suite:"
    php phpunit.phar --bootstrap "$parentdir/lib/random.php" "$parentdir/tests/unit"
    if [ $? -ne 0 ]; then
        # Test failure
        exit 1
    fi
    echo "With open_basedir enabled:"
    php -d open_basedir=$parentdir phpunit.phar --bootstrap "$parentdir/vendor/autoload.php" "$parentdir/tests/unit"
    if [ $? -ne 0 ]; then
        # Test failure
        exit 1
    fi
    echo "With open_basedir enabled, allowing /dev:"
    php -d open_basedir=$parentdir:/dev phpunit.phar --bootstrap "$parentdir/vendor/autoload.php" "$parentdir/tests/unit"
    if [ $? -ne 0 ]; then
        # Test failure
        exit 1
    fi
    echo "With mbstring.func_overload enabled:"
    php -d mbstring.func_overload=7 phpunit.phar --bootstrap "$parentdir/vendor/autoload.php" "$parentdir/tests/unit"
    if [ $? -ne 0 ]; then
        # Test failure
        exit 1
    fi

    if [[ "$testeach" == "1" ]]; then
        echo "    CAPICOM:"
        php phpunit.phar --bootstrap "$parentdir/tests/specific/capicom.php" "$parentdir/tests/unit"
        echo "    /dev/urandom:"
        php phpunit.phar --bootstrap "$parentdir/tests/specific/dev_urandom.php" "$parentdir/tests/unit"
        echo "    libsodium:"
        php phpunit.phar --bootstrap "$parentdir/tests/specific/libsodium.php" "$parentdir/tests/unit"
        echo "    mcrypt:"
        php phpunit.phar --bootstrap "$parentdir/tests/specific/mcrypt.php" "$parentdir/tests/unit"
        echo "    openssl:"
        php phpunit.phar --bootstrap "$parentdir/tests/specific/openssl.php" "$parentdir/tests/unit"
    fi

    # Should we perform full statistical analyses?
    if [[ "$fulltest" == "1" ]]; then
        php phpunit.phar --bootstrap "$parentdir/vendor/autoload.php" "$parentdir/tests/full"
        if [ $? -ne 0 ]; then
            # Test failure
            exit 1
        fi
    fi
    # Cleanup
    if [[ "$clean" == "1" ]]; then
        echo -e "\033[32mCleaning Up!\033[0m"
        rm -f phpunit.phar
        rm -f phpunit.phar.asc
    fi
else
    echo
    chmod -x phpunit.phar
    mv phpunit.phar /tmp/bad-phpunit.phar
    mv phpunit.phar.asc /tmp/bad-phpunit.phar.asc
    echo -e "\033[31mSignature did not match! Check /tmp/bad-phpunit.phar for trojans\033[0m"
    exit 1
fi
