<?php declare(strict_types=1);

namespace Posternak\ComposerFile;

use Posternak\JsonFile\JsonFile;
use RuntimeException;

class ComposerJsonFile extends JsonFile {
    public function getPackageVersionConstraint(string $packageName): string {
        foreach (['require', 'require-dev'] as $section) {
            if ($this->has("$section.$packageName")) {
                $constraint = $this->get("$section.$packageName");
                if (!is_string($constraint)) {
                    throw new RuntimeException("Constraint for '$packageName' in '$section' is not a string");
                }
                return $constraint;
            }
        }
        throw new RuntimeException("Package '$packageName' not found in 'require' or 'require-dev'");
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
        return $this->requirementsSectionValidated('require');
    }

    /** @return array<array-key, string> */
    public function getRequireDev(): array {
        return $this->requirementsSectionValidated('require-dev');
    }

    /**
     * @return array<string, string>
     */
    private function requirementsSectionValidated(string $sectionName): array {
        if (!$this->has($sectionName)) {
            return [];
        }

        $section = $this->get($sectionName);
        if (!is_array($section)) {
            throw new RuntimeException("Expected '$sectionName' section in composer.json to be a JSON object, got " . gettype($section));
        }

        foreach ($section as $packageName => $constraint) {
            if (!is_string($constraint)) {
                throw new RuntimeException("Package constraint should be a string");
            }
            if (!is_string($packageName)) {
                throw new RuntimeException("Package name should be a string");
            }
        }

        return $section;
    }
}
