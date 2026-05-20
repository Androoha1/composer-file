<?php declare(strict_types=1);

namespace Posternak\ComposerFile;

use Posternak\JsonFile\JsonFile;

class ComposerLockFile {
    private JsonFile $jsonFileManager;

    public function __construct(string $composerLockPath) {
        $this->jsonFileManager = JsonFile::getInstance($composerLockPath);
    }

    public function getInstalledPackageVersion(string $packageName): ?string {
        $packageInfo = $this->getPackageInfo($packageName);
        return $packageInfo['version'] ?? null;
    }

    public function getPackageInfo(string $packageName): ?array {
        $allPackages = array_merge(
            $this->jsonFileManager->getValueByPath('packages'),
            $this->jsonFileManager->getValueByPath('packages-dev') ?? []
        );

        foreach ($allPackages as $package) {
            if ($package['name'] === $packageName) {
                return $package;
            }
        }

        return null;
    }
}