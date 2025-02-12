<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Xandrw\ArchitectureEnforcer\Commands\LayerFileScanner;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments\GetConfigArgument;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments\GetIgnoreOption;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments\GetSourceArgument;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Renderers\DefaultRenderer;

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
        $this->addArgument(name: 'source', mode: InputArgument::REQUIRED, description: 'Path to app files');
        $this->addArgument(name: 'config', mode: InputArgument::REQUIRED, description: 'Path to config file');
        $this->addOption(
            name: 'ignore',
            shortcut: 'i',
            mode: InputArgument::IS_ARRAY,
            description: 'Comma-separated list of ignored paths from the current directory (e.g.: vendor,var,tests)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $config = (new GetConfigArgument())($input);
            $ignore = (new GetIgnoreOption())($input, $config->getIgnore());
            $source = (new GetSourceArgument())($input, $ignore);
        } catch (Exception $e) {
            $exceptionClass = $e::class;
            $output->writeln("<fg=red;options=bold>$exceptionClass: {$e->getMessage()}</>");
            return Command::FAILURE;
        }

        $stopwatch = new Stopwatch();
        $stopwatch->start(self::class);
        $scanner = new LayerFileScanner($config->getArchitecture());
        $scannedLayerFiles = $scanner->scan($source, $ignore);

        try {
            $hasErrors = (new DefaultRenderer($output, $stopwatch, $source, $ignore))($scannedLayerFiles);
        } catch (Exception $e) {
            $exceptionClass = $e::class;
            $output->writeln("<fg=red;options=bold>$exceptionClass: {$e->getMessage()}</>");
            return Command::FAILURE;
        }

        return $hasErrors ? Command::FAILURE : Command::SUCCESS;
    }
}
