<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Xandrw\ArchitectureEnforcer\Domain\Architecture;
use Xandrw\ArchitectureEnforcer\Infrastructure\ArchitectureDirectoryScanner;

class PureOption
{
    public function __invoke(InputInterface $input, string $source, Architecture $architecture): void
    {
        if (!$input->hasOption('pure')) return;

        (new ArchitectureDirectoryScanner())->scan($source, $architecture);
    }

    public static function addTo(Command $command): void
    {
        $command->addOption(
            name: 'pure',
            shortcut: 'p',
            mode: InputOption::VALUE_NONE,
            description: 'Architecture directories must exist',
        );
    }
}
