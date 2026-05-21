<?php declare(strict_types=1);

namespace Posternak\ComposerFile;

use Posternak\JsonFile\JsonFile;
use RuntimeException;

class ComposerLockFile {
    private JsonFile $jsonFile;

    public function __construct(string $composerLockPath) {
        $this->jsonFile = new JsonFile($composerLockPath);
    }

    public function getInstalledPackageVersion(string $packageName): string {
        $version = $this->getPackageInfo($packageName)['version'] ?? null;
        if (!is_string($version)) {
            throw new RuntimeException("Package '$packageName' has no string 'version' field in composer.lock");
        }
        return $version;
    }

    /** @return array<array-key, mixed> */
    public function getPackageInfo(string $packageName): array {
        foreach ($this->getInstalledPackages() as $package) {
            if (($package['name'] ?? null) === $packageName) {
                return $package;
            }
        }

        throw new RuntimeException("Package '$packageName' is not installed");
    }

    /** @return list<array<array-key, mixed>> */
    public function getInstalledPackages(): array {
        return array_merge(
            $this->packagesSectionValidated('packages'),
            $this->packagesSectionValidated('packages-dev'),
        );
    }

    /** @return list<array<array-key, mixed>> */
    private function packagesSectionValidated(string $sectionName): array {
        if (!$this->jsonFile->has($sectionName)) {
            return [];
        }

        $section = $this->jsonFile->get($sectionName);
        if (!is_array($section)) {
            throw new RuntimeException("Expected '$sectionName' section in composer.lock to be a JSON array, got " . gettype($section));
        }

        $packages = [];
        foreach ($section as $package) {
            if (!is_array($package)) {
                throw new RuntimeException("Each entry in '$sectionName' must be a JSON object");
            }
            $packages[] = $package;
        }

        return $packages;
    }
}
