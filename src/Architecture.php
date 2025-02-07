<?php

namespace Xandrw\ArchitectureEnforcer;

use LogicException;
use Xandrw\ArchitectureEnforcer\Invokers\ValidateArchitectureConflicts;

class Architecture
{
    /** @var array<string, Layer> */
    private array $layers = [];

    public function __construct(array $config)
    {
        if (!array_key_exists('architecture', $config)) {
            throw new LogicException("'architecture' key not set in config file");
        }

        if (!is_array($config['architecture'])) {
            throw new LogicException("'architecture' key must be an array in config file");
        }

        (new ValidateArchitectureConflicts())($config['architecture']);

        foreach ($config['architecture'] as $layerName => $childLayerNames) {
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