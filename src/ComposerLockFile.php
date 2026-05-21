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
        $result = [];
        foreach (array_merge($this->section('packages'), $this->section('packages-dev')) as $package) {
            if (is_array($package)) {
                $result[] = $package;
            }
        }
        return $result;
    }

    /** @return list<mixed> */
    private function section(string $name): array {
        $value = $this->jsonFile->has($name) ? $this->jsonFile->get($name) : [];
        return is_array($value) ? array_values($value) : [];
    }
}
