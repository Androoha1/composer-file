<?php declare(strict_types=1);

namespace Tests\Unit\ComposerLockFile;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Posternak\ComposerFile\ComposerLockFile;
use RuntimeException;

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
    public function throwsWhenAskingForVersionOfUninstalledPackage(): void {
        $lock = new ComposerLockFile(self::$composerLockFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Package 'vendor/does-not-exist' is not installed");

        $lock->getInstalledPackageVersion('vendor/does-not-exist');
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
    public function throwsWhenAskingForInfoOfUninstalledPackage(): void {
        $lock = new ComposerLockFile(self::$composerLockFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Package 'vendor/does-not-exist' is not installed");

        $lock->getPackageInfo('vendor/does-not-exist');
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

    #[Test]
    public function listsRuntimePackagesOnly(): void {
        $packages = (new ComposerLockFile(self::$composerLockFile))->getInstalledRuntimePackages();

        $names = array_map(static fn (array $p): mixed => $p['name'] ?? null, $packages);
        $this->assertSame(['laravel/framework', 'thecodingmachine/safe'], $names);
    }

    #[Test]
    public function listsDevPackagesOnly(): void {
        $packages = (new ComposerLockFile(self::$composerLockFile))->getInstalledDevPackages();

        $names = array_map(static fn (array $p): mixed => $p['name'] ?? null, $packages);
        $this->assertSame(['phpunit/phpunit'], $names);
    }
}
