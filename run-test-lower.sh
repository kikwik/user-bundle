#!/bin/bash
php8.2 composer.phar update --no-blocking --prefer-lowest --with-all-dependencies
php8.2 vendor/bin/phpunit --testdox $@