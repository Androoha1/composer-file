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
        $packages = array_merge(
            $this->get('require'),
            $this->get('require-dev'),
        );

        return $packages[$packageName] ?? null;
    }

    public function setPackageVersionConstraint(string $packageName, string $versionConstraint): self {
        $section = match (true) {
            isset($this->get('require')[$packageName])     => 'require',
            isset($this->get('require-dev')[$packageName]) => 'require-dev',
            default => throw new RuntimeException("Package '$packageName' not found in 'require' or 'require-dev'"),
        };

        $manipulator = new JsonManipulator(file_get_contents($this->filePath));
        $manipulator->addLink($section, $packageName, $versionConstraint);
        file_put_contents($this->filePath, $manipulator->getContents());

        return $this;
    }
}
