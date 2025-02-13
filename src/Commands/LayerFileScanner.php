<?php

namespace Xandrw\ArchitectureEnforcer\Commands;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Xandrw\ArchitectureEnforcer\Domain\Architecture;
use Xandrw\ArchitectureEnforcer\Domain\LayerFile;

class LayerFileScanner
{
    public function __construct(private readonly Architecture $architecture)
    {
    }

    /** @return LayerFile[] */
    public function scan(string $directory, array $ignoredPaths): array
    {
        $scannedLayerFiles = [];

        /** @var SplFileInfo $scannedFile */
        foreach ($this->getFiles($directory, $ignoredPaths) as $scannedFile) {
            $scannedLayerFiles[] = new LayerFile($scannedFile, $this->architecture);
        }

        return $scannedLayerFiles;
    }

    protected function getFiles(string $directory, array $ignoredPaths): iterable
    {
        return Finder::create()
            ->files()->in($directory)
            ->exclude($ignoredPaths)
            ->ignoreDotFiles(true)
            ->name('*.php');
    }
}
