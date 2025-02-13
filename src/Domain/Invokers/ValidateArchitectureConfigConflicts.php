<?php

namespace Xandrw\ArchitectureEnforcer\Domain\Invokers;

use Symfony\Component\Console\Exception\LogicException;

class ValidateArchitectureConfigConflicts
{
    /**
     * @throws LogicException
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
     * and throws a LogicException with the explanation message
     * @throws LogicException
     */
    private function validateLayerConflicts(string $layerName, array $verifiedLayers): void
    {
        foreach ($verifiedLayers as $verifiedLayerName) {
            if (str_starts_with($layerName, $verifiedLayerName) || str_starts_with($verifiedLayerName, $layerName)) {
                throw new LogicException("Layer conflict between $verifiedLayerName and $layerName");
            }
        }
    }
}
