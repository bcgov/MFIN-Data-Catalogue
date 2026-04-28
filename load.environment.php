<?php

/**
 * @file
 * This file is included very early. See autoload.files in composer.json.
 * @see https://getcomposer.org/doc/04-schema.md#files
 */


// This file is consumed by docker/Dockerfile.

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->usePutenv(TRUE);

$paths = [
  __DIR__ . '/.env',
];

foreach ($paths as $path) {
  try {
    $dotenv->load($path);
  }
  catch (\Exception $exception) {
    // Void.
  }
}
