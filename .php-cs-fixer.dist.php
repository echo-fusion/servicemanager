<?php

declare(strict_types=1);

use PhpCsFixer\Config;

$config = new Config();
$config->setUsingCache(false);
$config
    ->getFinder()
    ->ignoreDotFiles(false)
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return $config;