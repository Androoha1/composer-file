<?php declare(strict_types=1);

namespace Tests\Unit\ComposerLockFile;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Posternak\ComposerFile\ComposerLockFile;

final class ComposerLockFileTest extends TestCase {
    private static string $composerLockFile = __DIR__ . '/Mocks/composer-lock-file.json';

    #[Test]
    public function returnsVersionForPackageInPackagesSection(): void {
        $lock = new ComposerLockFile(self::$composerLockFile);
        $this->assertSame('v12.8.1', $lock->getInstalledPackageVersion('laravel/framework'));
    }

    #[Test]
    public function returnsVersionForPackageInPackagesDevSection(): void {
        $lock = new ComposerLockFile(self::$composerLockFile);
        $this->assertSame('12.1.2', $lock->getInstalledPackageVersion('phpunit/phpunit'));
    }

    #[Test]
    public function returnsNullWhenPackageIsNotInstalled(): void {
        $lock = new ComposerLockFile(self::$composerLockFile);
        $this->assertNull($lock->getInstalledPackageVersion('vendor/does-not-exist'));
    }

    #[Test]
    public function returnsFullPackageEntryFromGetPackageInfo(): void {
        $lock = new ComposerLockFile(self::$composerLockFile);
        $this->assertSame(
            ['name' => 'laravel/framework', 'version' => 'v12.8.1', 'type' => 'library'],
            $lock->getPackageInfo('laravel/framework'),
        );
    }

    #[Test]
    public function getPackageInfoReturnsNullForUnknownPackage(): void {
        $lock = new ComposerLockFile(self::$composerLockFile);
        $this->assertNull($lock->getPackageInfo('vendor/does-not-exist'));
    }

    #[Test]
    public function listsAllInstalledPackagesAcrossBothSections(): void {
        $packages = (new ComposerLockFile(self::$composerLockFile))->getInstalledPackages();

        $names = array_map(static fn (array $p): mixed => $p['name'] ?? null, $packages);
        $this->assertSame(
            ['laravel/framework', 'thecodingmachine/safe', 'phpunit/phpunit'],
            $names,
        );
    }
}
