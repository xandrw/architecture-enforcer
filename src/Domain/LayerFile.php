<?php

namespace Xandrw\ArchitectureEnforcer\Domain;

use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\Finder\SplFileInfo;
use Xandrw\ArchitectureEnforcer\Domain\Exceptions\ArchitectureException;
use Xandrw\ArchitectureEnforcer\Domain\Invokers\GetUseStatementsWithLines;

class LayerFile
{
    public readonly string $fileContents;
    public readonly ?string $namespace;
    public readonly ?Layer $layer;

    public function __construct(
        public readonly SplFileInfo $fileInfo,
        public readonly Architecture $architecture,
    )
    {
        $this->fileContents = $this->fileInfo->getContents();
        $this->initializeNamespace();
        $this->initializeLayer();
    }

    public function getFileName(): string
    {
        return $this->fileInfo->getFilename();
    }

    /** @return ArchitectureException[] */
    public function validate(): array
    {
        if ($this->layer === null) return [];

        $errors = [];

        foreach ($this->getUsedNamespacesWithLines() as [$namespace, $line]) {
            if ($this->canUseNamespace($namespace)) continue;
            $errors[] = new ArchitectureException($this, $line, $namespace);
        }

        return $errors;
    }

    public function __toString(): string
    {
        return $this->namespace . '\\' . $this->fileInfo->getFilenameWithoutExtension();
    }

    protected function getUsedNamespacesWithLines(): array
    {
        return (new GetUseStatementsWithLines())($this->fileContents);
    }

    private function canUseNamespace(string $namespace): bool
    {
        if ($this->layer === null) return false;

        $usedLayerName = $this->getLayerNameOrNamespace($namespace);
        $strict = $this->layer->isStrict();

        if ($this->layer->name === $usedLayerName) return true;

        if (!$strict && !$this->architecture->hasLayer($usedLayerName)) return true;

        if ($this->layer->hasChildLayer($usedLayerName)) return true;

        if ($strict && !$this->architecture->hasLayer($usedLayerName)) {
            return $this->isInternal($namespace);
        }

        return false;
    }

    private function getLayerNameOrNamespace(?string $namespace): ?string
    {
        if ($namespace === null) return null;

        $layer = $this->architecture->getLayerByNamespace($namespace);

        if ($layer !== null) return $layer;

        return $namespace;
    }

    private function initializeNamespace(): void
    {
        if (preg_match('/namespace\s+(?<namespace>[^;]+);/', $this->fileContents, $matches)) {
            $this->namespace = trim($matches['namespace']);
            return;
        }

        $this->namespace = null;
    }

    private function initializeLayer(): void
    {
        $this->layer = $this->architecture->getLayerByNamespace($this->namespace);
    }

    private function isInternal(string $useStatement): bool
    {
        return $this->isInternalClass($useStatement) || $this->isInternalFunction($useStatement);
    }

    private function isInternalClass(string $useStatement): bool
    {
        if (class_exists($useStatement)) {
            return (new ReflectionClass($useStatement))->isInternal();
        }

        return false;
    }

    private function isInternalFunction(string $useStatement): bool
    {
        if (function_exists($useStatement)) {
            return (new ReflectionFunction($useStatement))->isInternal();
        }

        return false;
    }
}
