<?php declare(strict_types=1);

namespace Tests\Unit\ComposerJsonFile;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Posternak\ComposerFile\ComposerJsonFile;
use RuntimeException;

final class ComposerJsonFileTest extends TestCase {
    private static string $realLifeComposerJsonFile = __DIR__ . '/Mocks/real-life-composer-json-file.json';

    private string $tmpFile;

    protected function setUp(): void {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'composer-json-') . '.json';
        copy(self::$realLifeComposerJsonFile, $this->tmpFile);
    }

    protected function tearDown(): void {
        @unlink($this->tmpFile);
    }

    #[Test]
    #[DataProvider('provideForGetsPackageVersionConstraint')]
    public function getsPackageVersionConstraint(string $packageName, string $expectedVersionConstraint): void {
        $composerJsonFile = new ComposerJsonFile(self::$realLifeComposerJsonFile);
        $this->assertSame($expectedVersionConstraint, $composerJsonFile->getPackageVersionConstraint($packageName));
    }

    /**
     * @return list<array{string, string}>
     */
    public static function provideForGetsPackageVersionConstraint(): array {
        return [
            ['nova/billing', '^v2.0.3'],
            ['symfony/console', '^7.1.0'],
            ['webmozart/assert', '^1.11.0'],
        ];
    }

    #[Test]
    public function throwsWhenGettingConstraintForUnknownPackage(): void {
        $composerJsonFile = new ComposerJsonFile(self::$realLifeComposerJsonFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Package 'vendor/does-not-exist' not found in 'require' or 'require-dev'");

        $composerJsonFile->getPackageVersionConstraint('vendor/does-not-exist');
    }

    #[Test]
    public function updatesConstraintInRequireSection(): void {
        $composerJsonFile = new ComposerJsonFile($this->tmpFile);
        $composerJsonFile->setPackageVersionConstraint('symfony/console', '^7.2.0');

        $reloaded = new ComposerJsonFile($this->tmpFile);
        $this->assertSame('^7.2.0', $reloaded->getPackageVersionConstraint('symfony/console'));
    }

    #[Test]
    public function updatesConstraintInRequireDevSection(): void {
        $composerJsonFile = new ComposerJsonFile($this->tmpFile);
        $composerJsonFile->setPackageVersionConstraint('phpunit/phpunit', '^13.0.0');

        $reloaded = new ComposerJsonFile($this->tmpFile);
        $this->assertSame('^13.0.0', $reloaded->getPackageVersionConstraint('phpunit/phpunit'));
    }

    #[Test]
    public function throwsWhenSettingConstraintForUnknownPackage(): void {
        $composerJsonFile = new ComposerJsonFile($this->tmpFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Package 'vendor/does-not-exist' not found in 'require' or 'require-dev'");

        $composerJsonFile->setPackageVersionConstraint('vendor/does-not-exist', '^1.0.0');
    }

    #[Test]
    public function addsNewPackageToRequire(): void {
        $composerJsonFile = new ComposerJsonFile($this->tmpFile);
        $composerJsonFile->addPackage('vendor/new-runtime-dep', '^1.0.0');

        $reloaded = new ComposerJsonFile($this->tmpFile);
        $this->assertSame('^1.0.0', $reloaded->getPackageVersionConstraint('vendor/new-runtime-dep'));
    }

    #[Test]
    public function addsNewPackageToRequireDev(): void {
        $composerJsonFile = new ComposerJsonFile($this->tmpFile);
        $composerJsonFile->addPackage('vendor/new-dev-dep', '^2.0.0', dev: true);

        $reloaded = new ComposerJsonFile($this->tmpFile);
        $this->assertSame('^2.0.0', $reloaded->getPackageVersionConstraint('vendor/new-dev-dep'));
    }

    #[Test]
    public function throwsWhenAddingPackageThatIsAlreadyListed(): void {
        $composerJsonFile = new ComposerJsonFile($this->tmpFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Package 'symfony/console' is already listed");

        $composerJsonFile->addPackage('symfony/console', '^7.2.0');
    }

    #[Test]
    public function removesPackageFromRequire(): void {
        $composerJsonFile = new ComposerJsonFile($this->tmpFile);
        $composerJsonFile->removePackage('symfony/console');

        $reloaded = new ComposerJsonFile($this->tmpFile);
        $this->assertFalse($reloaded->has('require.symfony/console'));
    }

    #[Test]
    public function removesPackageFromRequireDev(): void {
        $composerJsonFile = new ComposerJsonFile($this->tmpFile);
        $composerJsonFile->removePackage('phpunit/phpunit');

        $reloaded = new ComposerJsonFile($this->tmpFile);
        $this->assertFalse($reloaded->has('require-dev.phpunit/phpunit'));
    }

    #[Test]
    public function getsRequireSectionAsMap(): void {
        $require = (new ComposerJsonFile(self::$realLifeComposerJsonFile))->getRequire();

        $this->assertSame('^7.1.0', $require['symfony/console']);
        $this->assertSame('^v2.0.3', $require['nova/billing']);
        $this->assertArrayNotHasKey('phpunit/phpunit', $require);
    }

    #[Test]
    public function getsRequireDevSectionAsMap(): void {
        $requireDev = (new ComposerJsonFile(self::$realLifeComposerJsonFile))->getRequireDev();

        $this->assertSame('^11.5.3', $requireDev['phpunit/phpunit']);
        $this->assertArrayNotHasKey('symfony/console', $requireDev);
    }

    #[Test]
    public function removingUnknownPackageIsANoOp(): void {
        $composerJsonFile = new ComposerJsonFile($this->tmpFile);
        $composerJsonFile->removePackage('vendor/does-not-exist');

        $this->assertFalse($composerJsonFile->has('require.vendor/does-not-exist'));
        $this->assertFalse($composerJsonFile->has('require-dev.vendor/does-not-exist'));
    }
}
