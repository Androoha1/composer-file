# composer-file

[![CI](https://github.com/Androoha1/composer-file/actions/workflows/ci.yml/badge.svg)](https://github.com/Androoha1/composer-file/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/posternak/composer-file.svg)](https://packagist.org/packages/posternak/composer-file)
[![PHP Version](https://img.shields.io/packagist/php-v/posternak/composer-file.svg)](https://packagist.org/packages/posternak/composer-file)
[![License](https://img.shields.io/packagist/l/posternak/composer-file.svg)](LICENSE)

The joyful way to programmatically read and edit `composer.json` and `composer.lock` in PHP — drop it into any script or automation.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require posternak/composer-file
```

## Usage

The package ships two classes — one for each Composer file.

### `ComposerJsonFile` — read and edit `composer.json`

Given a `composer.json` like:

```json
{
    "name": "acme/app",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.8.1",
        "thecodingmachine/safe": "^3.0.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^12.1.2"
    }
}
```

You can read, add, update, remove and iterate over its dependencies:

```php
use Posternak\ComposerFile\ComposerJsonFile;

$file = new ComposerJsonFile('/path/to/composer.json');

// Read a constraint from either `require` or `require-dev`
$file->getPackageVersionConstraint('laravel/framework');   // "^12.8.1"
$file->getPackageVersionConstraint('phpunit/phpunit');     // "^12.1.2"
$file->getPackageVersionConstraint('vendor/missing');      // throws — guard with has() if unsure

// Update an existing constraint
$file->setPackageVersionConstraint('laravel/framework', '^12.9.0');

// Add a new dependency
$file->addPackage('symfony/console', '^7.2');
$file->addPackage('mockery/mockery', '^1.6', dev: true);

// Remove a dependency (no-op if it's not there)
$file->removePackage('thecodingmachine/safe');

// Iterate dependencies as a name => constraint map
foreach ($file->getRequire() as $name => $constraint) {
    echo "$name => $constraint\n";
}
foreach ($file->getRequireDev() as $name => $constraint) {
    // ...
}
```

A few rules to keep in mind:

- `setPackageVersionConstraint()` updates only — it throws if the package isn't already declared in `require` or `require-dev`. Use `addPackage()` for new ones.
- `addPackage()` adds only — it throws if the package is already declared anywhere. Use `setPackageVersionConstraint()` to change an existing constraint.
- `removePackage()` is idempotent — calling it on a package that isn't declared is a no-op.

`ComposerJsonFile` extends [`Posternak\JsonFile\JsonFile`](https://packagist.org/packages/posternak/json-file), so you also get the generic `has()`, `get()`, `set()`, `remove()` and `save()` methods on it — handy for poking at any other field in the file (autoload, scripts, config, …).

### `ComposerLockFile` — read installed versions from `composer.lock`

```php
use Posternak\ComposerFile\ComposerLockFile;

$lock = new ComposerLockFile('/path/to/composer.lock');

// Installed version of a single package
$lock->getInstalledPackageVersion('laravel/framework');   // "v12.8.1"
$lock->getInstalledPackageVersion('vendor/missing');      // throws — package not installed

// Full lock entry — everything composer.lock records for the package
$lock->getPackageInfo('laravel/framework');
// => ["name" => "laravel/framework", "version" => "v12.8.1", "type" => "library", ...]

// Walk the lock file — all installed, just runtime, or just dev
$lock->getInstalledPackages();          // packages + packages-dev
$lock->getInstalledRuntimePackages();   // packages only
$lock->getInstalledDevPackages();       // packages-dev only
```

Both `getInstalledPackageVersion()` and `getPackageInfo()` throw `RuntimeException` if the package isn't installed.

## Example: bump every package under a vendor prefix

A short script that updates every `laravel/*` constraint in a project's `composer.json`:

```php
use Posternak\ComposerFile\ComposerJsonFile;

$file = new ComposerJsonFile($argv[1] ?? __DIR__ . '/composer.json');

foreach ($file->getRequire() as $name => $constraint) {
    if (str_starts_with($name, 'laravel/')) {
        $file->setPackageVersionConstraint($name, '^12.9.0');
    }
}
```

The same shape works for any "for each package, do X" automation — audits, batch upgrades, CI lockstep checks across multiple repos.

## License

Released under the [MIT License](LICENSE).
