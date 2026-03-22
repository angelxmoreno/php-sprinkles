<?php
declare(strict_types=1);

use Cake\Core\Configure;
use RuntimeException;

$autoloadCandidates = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php',
];

$autoloadLoaded = false;
foreach ($autoloadCandidates as $autoload) {
    if (!is_file($autoload)) {
        continue;
    }

    require $autoload;
    $autoloadLoaded = true;
    break;
}

if (!$autoloadLoaded) {
    throw new RuntimeException('Could not locate Composer autoload.php for PHPSprinklesCors tests.');
}

Configure::write('App.encoding', 'UTF-8');
