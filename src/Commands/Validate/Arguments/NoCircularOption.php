<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Xandrw\ArchitectureEnforcer\Domain\Architecture;

class NoCircularOption
{
    public function __invoke(InputInterface $input, Architecture $architecture): void
    {
        $noCircular = $input->hasOption('no-circular');

        if (!$noCircular) return;

        $processedLayers = [];

        foreach ($architecture->getLayers() as $layer) {
            $processedLayers[] = $layer;

            if (count($processedLayers) === 1) continue;

            foreach ($processedLayers as $processedLayer) {
                if ($processedLayer->name === $layer->name) continue;

                if ($processedLayer->hasChildLayer($layer->name) && $layer->hasChildLayer($processedLayer->name)) {
                    throw new LogicException("Circular dependency between $layer->name and $processedLayer->name");
                }
            }
        }
    }
}
