<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Security\Encrypter;

$value = $argv[1] ?? '';

if ($value === '') {
    fwrite(STDERR, "Error: No value provided.\nUsage: php bin/encrypt.php <value>\n");
    exit(1);
}

$encrypter = new Encrypter;
echo $encrypter->encrypt($value).PHP_EOL;
