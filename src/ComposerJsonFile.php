<?php declare(strict_types=1);

namespace Posternak\ComposerFile;

use Composer\Json\JsonManipulator;
use Posternak\JsonFile\JsonFile;
use RuntimeException;

class ComposerJsonFile extends JsonFile {
    public function __construct(private readonly string $filePath) {
        parent::__construct($filePath);
    }

    public function getPackageVersionConstraint(string $packageName): ?string {
        $packages = array_merge($this->section('require'), $this->section('require-dev'));
        $value = $packages[$packageName] ?? null;

        return is_string($value) ? $value : null;
    }

    public function setPackageVersionConstraint(string $packageName, string $versionConstraint): self {
        $section = match (true) {
            isset($this->section('require')[$packageName])     => 'require',
            isset($this->section('require-dev')[$packageName]) => 'require-dev',
            default => throw new RuntimeException("Package '$packageName' not found in 'require' or 'require-dev'"),
        };

        if (false === $contents = file_get_contents($this->filePath)) {
            throw new RuntimeException("Cannot read '{$this->filePath}'");
        }

        $manipulator = new JsonManipulator($contents);
        $manipulator->addLink($section, $packageName, $versionConstraint);
        file_put_contents($this->filePath, $manipulator->getContents());

        return $this;
    }

    /** @return array<array-key, mixed> */
    private function section(string $name): array {
        $value = $this->has($name) ? $this->get($name) : [];
        return is_array($value) ? $value : [];
    }
}
