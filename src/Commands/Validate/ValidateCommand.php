<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments\GetConfigArgument;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments\GetIgnoreOption;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments\GetSourceArgument;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments\NoCircularOption;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments\PureOption;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Renderers\DefaultRenderer;
use Xandrw\ArchitectureEnforcer\Infrastructure\LayerFileScanner;

/** @SuppressUnused */
#[AsCommand(
    name: 'validate',
    description: 'Validate Architecture layers based on config',
    aliases: ['v']
)]
class ValidateCommand extends Command
{
    protected function configure(): void
    {
        GetSourceArgument::addTo($this);
        GetConfigArgument::addTo($this);
        GetIgnoreOption::addTo($this);
        NoCircularOption::addTo($this);
        PureOption::addTo($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $config = (new GetConfigArgument())($input);
            $ignore = (new GetIgnoreOption())($input, $config->getIgnore());
            $source = (new GetSourceArgument())($input, $ignore);
            (new NoCircularOption())($input, $config->getArchitecture());
            (new PureOption())($input, $source, $config->getArchitecture());
        } catch (Exception $e) {
            DefaultRenderer::outputException($output, $e);
            return Command::FAILURE;
        }

        $stopwatch = new Stopwatch();
        $stopwatch->start(self::class);
        $scanner = new LayerFileScanner($config->getArchitecture());
        $scannedLayerFiles = $scanner->scan($source, $ignore);

        try {
            $hasErrors = (new DefaultRenderer($output, $stopwatch, $source, $ignore))($scannedLayerFiles);
        } catch (Exception $e) {
            DefaultRenderer::outputException($output, $e);
            return Command::FAILURE;
        }

        return $hasErrors ? Command::FAILURE : Command::SUCCESS;
    }
}
