# composer-file

The joyful way to programmatically read and edit `composer.json` and `composer.lock` in PHP — drop it into any script or automation.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require posternak/composer-file
```

## Usage

### `ComposerJsonFile` — read and update package constraints

Suppose you have a `composer.json` like this:

```json
{
    "name": "acme/app",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^v12.8.1",
        "thecodingmachine/safe": "^v3.0.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^12.1.2"
    }
}
```

You can look up and update version constraints without caring whether the package lives in `require` or `require-dev`:

```php
use Posternak\ComposerFile\ComposerJsonFile;

$file = new ComposerJsonFile('/path/to/composer.json');

// Read a constraint from either `require` or `require-dev`
$file->getPackageVersionConstraint('laravel/framework');   // "^v12.8.1"
$file->getPackageVersionConstraint('phpunit/phpunit');     // "^12.1.2"
$file->getPackageVersionConstraint('vendor/missing');      // throws — guard with has() if unsure

// Update an existing constraint in place — formatting and key order
// of composer.json are preserved
$file->setPackageVersionConstraint('laravel/framework', '^v12.9.0');
$file->setPackageVersionConstraint('phpunit/phpunit', '^13.0.0');
```

`setPackageVersionConstraint` updates only — it throws `RuntimeException` if the package isn't already declared in either `require` or `require-dev`.

### `ComposerLockFile` — read installed versions from a lock file

Given a `composer.lock`, you can ask which version of a package is actually installed:

```php
use Posternak\ComposerFile\ComposerLockFile;

$lock = new ComposerLockFile('/path/to/composer.lock');

$lock->getInstalledPackageVersion('laravel/framework');   // "v12.8.1"
$lock->getInstalledPackageVersion('phpunit/phpunit');     // "12.1.2"
$lock->getInstalledPackageVersion('vendor/missing');      // throws — package not installed

// Full lock entry (everything composer.lock records for the package)
$lock->getPackageInfo('laravel/framework');
// => ["name" => "laravel/framework", "version" => "v12.8.1", "type" => "library", ...]
// Also throws if the package isn't installed.
```

Both `packages` and `packages-dev` sections of the lock file are searched.

## License

MIT
