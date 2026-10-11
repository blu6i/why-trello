<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Security\Encrypter;

$value = $argv[1] ?? '';

if ($value === '') {
    fwrite(STDERR, "Error: No value provided.\nUsage: php bin/decrypt.php <ENC:...>\n");
    exit(1);
}

$encrypter = new Encrypter;
echo $encrypter->decrypt($value).PHP_EOL;
