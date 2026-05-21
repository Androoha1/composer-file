<?php declare(strict_types=1);

namespace Posternak\ComposerFile;

use Posternak\JsonFile\JsonFile;
use RuntimeException;

class ComposerJsonFile extends JsonFile {
    public function getPackageVersionConstraint(string $packageName): ?string {
        $packages = array_merge(
            $this->section('require'),
            $this->section('require-dev')
        );
        $versionConstraint = $packages[$packageName] ?? null;

        return is_string($versionConstraint) ? $versionConstraint : null;
    }

    public function setPackageVersionConstraint(string $packageName, string $versionConstraint): self {
        $section = match (true) {
            isset($this->section('require')[$packageName])     => 'require',
            isset($this->section('require-dev')[$packageName]) => 'require-dev',
            default => throw new RuntimeException("Package '$packageName' not found in 'require' or 'require-dev'"),
        };

        $this->set("$section.$packageName", $versionConstraint);
        $this->save();

        return $this;
    }

    public function addPackage(string $packageName, string $versionConstraint, bool $dev = false): self {
        if (isset($this->section('require')[$packageName]) || isset($this->section('require-dev')[$packageName])) {
            throw new RuntimeException("Package '$packageName' is already listed; use setPackageVersionConstraint() to update its constraint");
        }

        $section = $dev ? 'require-dev' : 'require';
        $this->set("$section.$packageName", $versionConstraint);
        $this->save();

        return $this;
    }

    public function removePackage(string $packageName): self {
        $section = match (true) {
            isset($this->section('require')[$packageName])     => 'require',
            isset($this->section('require-dev')[$packageName]) => 'require-dev',
            default => null,
        };
        if ($section === null) {
            return $this;
        }

        $this->remove("$section.$packageName");
        $this->save();

        return $this;
    }

    /** @return array<array-key, mixed> */
    private function section(string $path): array {
        $value = $this->has($path) ? $this->get($path) : [];
        return is_array($value) ? $value : [];
    }
}
