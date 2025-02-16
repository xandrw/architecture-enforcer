<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Xandrw\ArchitectureEnforcer\Domain\Architecture;

class NoCircularOption
{
    public function __invoke(InputInterface $input, Architecture $architecture): void
    {
        if (!$input->hasOption('no-circular')) return;

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

    public static function addTo(Command $command): void
    {
        $command->addOption(
            name: 'no-circular',
            shortcut: 'c',
            mode: InputOption::VALUE_NONE,
            description: 'Restrict circular dependencies between layers',
        );
    }
}
