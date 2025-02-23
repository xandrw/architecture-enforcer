<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Xandrw\ArchitectureEnforcer\Commands\Validate\ValidateCommand;
use Xandrw\ArchitectureEnforcer\Domain\Architecture;
use Xandrw\ArchitectureEnforcer\Infrastructure\ArchitectureDirectoryScanner;

class PureOption
{
    public static function addTo(ValidateCommand $command): void
    {
        $command->addOption(
            name: 'pure',
            shortcut: 'p',
            mode: InputOption::VALUE_NONE,
            description: 'Architecture directories must exist',
        );
    }

    public function __invoke(InputInterface $input, string $source, Architecture $architecture): void
    {
        if (!$input->hasOption('pure')) return;

        (new ArchitectureDirectoryScanner())->scan($source, $architecture);
    }
}
