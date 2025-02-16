<?php

namespace Xandrw\ArchitectureEnforcer\Infrastructure;

use Symfony\Component\Console\Exception\LogicException;
use Xandrw\ArchitectureEnforcer\Domain\Architecture;
use Xandrw\ArchitectureEnforcer\Domain\Layer;

class ArchitectureDirectoryScanner
{
    public function scan(string $source, Architecture $architecture): void
    {
        $source = rtrim($source, '/');

        foreach ($architecture->getLayers() as $layer) {
            $layerRootName = Layer::removeNameNamespace($layer->name);
            $layerHeadlessPath = str_replace('\\', DIRECTORY_SEPARATOR, $layerRootName);
            $fullLayerPath = "$source/$layerHeadlessPath";

            if (!is_dir($fullLayerPath)) {
                throw new LogicException(
                    "$fullLayerPath not found, make sure your [$source] contains a [$layerHeadlessPath] directory",
                );
            }
        }
    }
}
