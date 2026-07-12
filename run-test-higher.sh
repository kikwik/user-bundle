#!/bin/bash
php8.5 composer.phar update
php8.5 vendor/bin/phpunit --testdox $@