<?php

namespace Xandrw\ArchitectureEnforcer\Domain;

class Layer
{
    public function __construct(
        public readonly string $name,
        public readonly array $childLayerNames,
    )
    {
    }

    public function isStrict(): bool
    {
        return in_array($this->name, $this->childLayerNames, true);
    }

    public function hasChildLayer(?string $layerNameOrNamespace): bool
    {
        if ($layerNameOrNamespace === null) return false;

        foreach ($this->childLayerNames as $childLayerName) {
            if (str_starts_with($layerNameOrNamespace, $childLayerName)) return true;
        }

        return false;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
