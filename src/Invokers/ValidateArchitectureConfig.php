<?php

namespace Xandrw\ArchitectureEnforcer\Invokers;

use Xandrw\ArchitectureEnforcer\Exceptions\ConfigException;

class ValidateArchitectureConfig
{
    /**
     * @throws ConfigException
     */
    public function __invoke(array $architectureConfig): void
    {
        if (empty($architectureConfig)) return;

        $verifiedLayers = [];

        foreach (array_keys($architectureConfig) as $layer) {
            $this->validateLayerConflicts($layer, $verifiedLayers);
            $verifiedLayers[] = $layer;
        }
    }

    /**
     * Validates if current layer contains or is contained (as a string) in one of the verified layers
     * and throws a ConfigException with the explanation message
     * @throws ConfigException
     */
    private function validateLayerConflicts(string $layer, array $processedLayers): void
    {
        foreach ($processedLayers as $validLayer) {
            if (str_starts_with($layer, $validLayer) || str_starts_with($validLayer, $layer)) {
                throw new ConfigException("Layer conflict between $validLayer and $layer");
            }
        }
    }
}