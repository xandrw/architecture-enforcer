<?php

namespace Xandrw\ArchitectureEnforcer;

class Architecture
{
    /** @var array<string, Layer> */
    private array $layers = [];

    public function __construct(array $architectureConfig)
    {
        foreach ($architectureConfig as $layerName => $childLayerNames) {
            $this->layers[$layerName] = new Layer($layerName, $childLayerNames);
        }
    }

    public function getLayerByNamespace(?string $namespace): ?Layer
    {
        if ($namespace === null) return null;

        foreach ($this->layers as $layerName => $layer) {
            if (str_starts_with($namespace, $layerName)) return $layer;
        }

        return null;
    }

    public function hasLayer(string $layerName): bool
    {
        return array_key_exists($layerName, $this->layers);
    }
}