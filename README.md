README
============================

The development toolchain shared by the projects and bundles: the `check` script the pre-push hook runs, the
tool configurations that script runs against, and the tools themselves. One `require-dev` entry replaces the
nine a project used to carry, and a change here reaches every installation with a `composer update` rather
than being copied by hand into repositories that then drift apart.

It is a development dependency and belongs in `require-dev`. Nothing in it is loaded at runtime, and
`composer install --no-dev` leaves it out of a deployed installation entirely.

```bash
composer require --dev davidhirtz/yii2-dev
```


The check
------------

`vendor/bin/check` runs the three gates a change has to pass — code style, static analysis and the test suite
— against the working tree, and is what the project's committed `.githooks/pre-push` runs. It reports and
never rewrites: a style failure asks for `composer cs-fix` rather than editing a tree whose commit is already
made.

```json
"scripts": {
    "check": "vendor/bin/check"
}
```

```sh
#!/bin/sh
exec "$(git rev-parse --show-toplevel)/vendor/bin/check"
```

It finds the installation by walking up from the working directory for `vendor/autoload.php`, so it can be
run from anywhere inside one. The test database is migrated first where there is an entry script to migrate
it with, and skipped where there is not.


The configurations
------------------

The paths to analyse are the one thing that differs between an application and a bundle, so they are what the
project's own config files pass in. Everything else — the rule sets, the level, the PHP version — lives here.

`.php-cs-fixer.php`:

```php
<?php

declare(strict_types=1);

$config = require __DIR__ . '/vendor/davidhirtz/yii2-dev/config/php-cs-fixer.php';

return $config([
    __DIR__ . '/app',
    __DIR__ . '/config',
    __DIR__ . '/resources/views',
    __DIR__ . '/tests',
]);
```

`rector.php` is the same shape against `config/rector.php`.

`phpstan.neon` includes `config/phpstan-app.neon`, which adds the skeleton's `Yii.php` and stub to the base
in `config/phpstan.neon`. A bundle, which is analysed from its own root rather than from an application that
has the skeleton in `vendor`, includes the base directly and names the skeleton itself.

```neon
includes:
    - vendor/davidhirtz/yii2-dev/config/phpstan-app.neon

parameters:
    paths:
        - app
        - config
        - resources/views
        - tests
```

`phpunit.xml` stays with the project. PHPUnit has no include mechanism of its own, and the bootstrap path and
cache directory differ per installation anyway.
