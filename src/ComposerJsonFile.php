<?php declare(strict_types=1);

namespace Posternak\ComposerFile;

use Posternak\JsonFile\JsonFile;
use RuntimeException;

class ComposerJsonFile extends JsonFile {
    public function getPackageVersionConstraint(string $packageName): ?string {
        foreach (['require', 'require-dev'] as $section) {
            if ($this->has("$section.$packageName")) {
                $constraint = $this->get("$section.$packageName");
                return is_string($constraint) ? $constraint : null;
            }
        }
        return null;
    }

    public function setPackageVersionConstraint(string $packageName, string $versionConstraint): void {
        $section = match (true) {
            $this->has("require.$packageName")     => 'require',
            $this->has("require-dev.$packageName") => 'require-dev',
            default => throw new RuntimeException("Package '$packageName' not found in 'require' or 'require-dev'"),
        };

        $this->set("$section.$packageName", $versionConstraint);
        $this->save();
    }

    public function addPackage(string $packageName, string $versionConstraint, bool $dev = false): void {
        if ($this->has("require.$packageName") || $this->has("require-dev.$packageName")) {
            throw new RuntimeException("Package '$packageName' is already listed; use setPackageVersionConstraint() to update its constraint");
        }

        $section = $dev ? 'require-dev' : 'require';
        $this->set("$section.$packageName", $versionConstraint);
        $this->save();
    }

    public function removePackage(string $packageName): void {
        $section = match (true) {
            $this->has("require.$packageName")     => 'require',
            $this->has("require-dev.$packageName") => 'require-dev',
            default => null,
        };
        if ($section === null) {
            return;
        }

        $this->remove("$section.$packageName");
        $this->save();
    }

    /** @return array<array-key, string> */
    public function getRequire(): array {
        return $this->packageMap('require');
    }

    /** @return array<array-key, string> */
    public function getRequireDev(): array {
        return $this->packageMap('require-dev');
    }

    /** @return array<array-key, string> */
    private function packageMap(string $section): array {
        $value = $this->has($section) ? $this->get($section) : [];
        return is_array($value) ? array_filter($value, 'is_string') : [];
    }
}
