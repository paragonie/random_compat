language: php
php:

- "7.0"
- "5.6"
- "5.5"
- "5.4"
- "5.3"
- "hhvm"


sudo: false

matrix:
    fast_finish: true
    allow_failures:
      - php: "hhvm"

install:

- composer install
- composer self-update
- composer update
- chmod +x ./phpunit.sh

script: ./phpunit.sh travis
