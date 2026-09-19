<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

// The shared PHP-CS-Fixer setup. The directories to scan are all that differs between installations, so they
// are the argument and everything else lives here:
//
//     $config = require __DIR__ . '/vendor/davidhirtz/yii2-dev/config/php-cs-fixer.php';
//
//     return $config([
//         __DIR__ . '/app',
//         __DIR__ . '/tests',
//     ]);
//
// A closure returned by a file rather than a class with a static method: PHP-CS-Fixer loads the config file
// itself and a project's `rector.php` is read by a tool that ships its own scoped autoloader, so neither can
// be relied on to have this package's classes available. `require` needs nothing registered.

return static function (array $paths, ?string $cacheFile = null): Config {
    // The cache belongs in `runtime` where a project already gitignores everything, and next to the config
    // file where a bundle has no such directory.
    $cacheFile ??= is_dir(getcwd() . '/runtime')
        ? getcwd() . '/runtime/.php-cs-fixer.cache'
        : getcwd() . '/.php-cs-fixer.cache';

    return (new Config())
        ->setParallelConfig(ParallelConfigFactory::detect())
        ->setRiskyAllowed(true)
        ->setRules([
            '@PSR12' => true,
            '@PHP8x3Migration:risky' => true,
        ])
        ->setCacheFile($cacheFile)
        ->setFinder(
            (new Finder())
                ->in($paths)
                // written by `./yii params` and never committed
                ->notName(['db.php', 'local.php', 'params.php'])
        );
};
