<?php declare(strict_types=1);

namespace Posternak\ComposerFile;

use Posternak\JsonFile\JsonFile;

class ComposerLockFile {
    private JsonFile $jsonFile;

    public function __construct(string $composerLockPath) {
        $this->jsonFile = new JsonFile($composerLockPath);
    }

    public function getInstalledPackageVersion(string $packageName): ?string {
        $version = $this->getPackageInfo($packageName)['version'] ?? null;

        return is_string($version) ? $version : null;
    }

    /** @return array<array-key, mixed>|null */
    public function getPackageInfo(string $packageName): ?array {
        foreach (array_merge($this->section('packages'), $this->section('packages-dev')) as $package) {
            if (is_array($package) && ($package['name'] ?? null) === $packageName) {
                return $package;
            }
        }

        return null;
    }

    /** @return list<mixed> */
    private function section(string $name): array {
        $value = $this->jsonFile->has($name) ? $this->jsonFile->get($name) : [];
        return is_array($value) ? array_values($value) : [];
    }
}
