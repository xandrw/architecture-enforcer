<?php

namespace Xandrw\ArchitectureEnforcer\Domain;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class LayerFilesScanner
{
    private Architecture $architecture;

    public function __construct(Architecture $architecture)
    {
        $this->architecture = $architecture;
    }

    /** @return LayerFile[] */
    public function scan(string $directory, array $ignoredPaths): array
    {
        $finder = (new Finder())
            ->files()->in($directory)
            ->exclude($ignoredPaths)
            ->ignoreDotFiles(true)
            ->name('*.php');

        $scannedLayerFiles = [];

        /** @var SplFileInfo $scannedFile */
        foreach ($finder as $scannedFile) {
            $scannedLayerFiles[] = new LayerFile($scannedFile, $this->architecture);
        }

        return $scannedLayerFiles;
    }
}