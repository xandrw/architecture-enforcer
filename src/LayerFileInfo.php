<?php

namespace Xandrw\ArchitectureEnforcer;

use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\Finder\SplFileInfo;

class LayerFileInfo
{
    private string $fileContents;
    private ?string $namespace = null;
    private ?string $layer = null;

    public function __construct(public readonly SplFileInfo $fileInfo, public readonly array $architecture)
    {
        $this->fileContents = $this->fileInfo->getContents();
    }

    public function getFileName(): string
    {
        return $this->fileInfo->getFilename();
    }

    public function getLayer(): ?string
    {
        if ($this->layer !== null) {
            return $this->layer;
        }
        return $this->layer = $this->getNamespaceLayer($this->getNamespace());
    }

    public function getNamespace(): ?string
    {
        if ($this->namespace !== null) {
            return $this->namespace;
        }
        if (preg_match('/namespace\s+(?<namespace>[^;]+);/', $this->fileContents, $matches)) {
            return $this->namespace = trim($matches['namespace']);
        }
        return $this->namespace = null;
    }

    public function getNamespaceLayer(?string $namespace): ?string
    {
        if ($namespace === null) return null;

        $arrayNamespace = explode('\\', $namespace);
        $root = $arrayNamespace[0];
        $layer = $arrayNamespace[1] ?? null;

        return $layer !== null ? "$root\\$layer" : $root;
    }

    public function getMarkedUseStatements(): array
    {
        return (new GetFileUseStatements())($this->fileContents);
    }

    /** @return ArchitectureException[] */
    public function validate(): array
    {
        if ($this->getLayer() === null) return [];

        $errors = [];

        foreach ($this->getMarkedUseStatements() as [$useStatement, $line]) {
            if ($this->canUseNamespace($useStatement)) continue;

            $errors[] = new ArchitectureException($this, $line, $useStatement);
        }

        return $errors;
    }

    public function __toString(): string
    {
        return $this->getNamespace() . '\\' . $this->fileInfo->getFilenameWithoutExtension();
    }

    private function canUseNamespace(string $usedUseStatement): bool
    {
        $thisLayer = $this->getLayer();

        if ($thisLayer === null) return false;

        $usedLayer = $this->getNamespaceLayer($usedUseStatement);
        $strict = in_array($thisLayer, $this->architecture[$thisLayer] ?? [], true);

        if ($thisLayer === $usedLayer) return true;

        if (!$strict && !array_key_exists($usedLayer, $this->architecture)) return true;

        if (in_array($usedLayer, $this->architecture[$thisLayer] ?? [], true)) return true;

        if ($strict && !array_key_exists($usedLayer, $this->architecture)) {
            return $this->isInternal($usedUseStatement);
        }

        return false;
    }

    private function isInternal(string $useStatement): bool
    {
        return $this->isInternalClass($useStatement) || $this->isInternalFunction($useStatement);
    }

    private function isInternalClass(string $useStatement): bool
    {
        if (class_exists($useStatement)) {
            $reflection = new ReflectionClass($useStatement);
            return $reflection->isInternal();
        }

        return false;
    }

    private function isInternalFunction(string $useStatement): bool
    {
        if (function_exists($useStatement)) {
            $reflection = new ReflectionFunction($useStatement);
            return $reflection->isInternal();
        }

        return false;
    }
}
