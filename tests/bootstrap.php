<?php

use Symfony\Component\Filesystem\Filesystem;

require __DIR__ . '/../vendor/autoload.php';

$filesystem = new Filesystem();
$filesystem->remove(__DIR__ . '/../var');
$filesystem->mkdir(__DIR__ . '/../var/log');