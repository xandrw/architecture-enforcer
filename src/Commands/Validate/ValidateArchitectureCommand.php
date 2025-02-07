<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Xandrw\ArchitectureEnforcer\LayerFilesScanner;

/** @SuppressUnused */
#[AsCommand(
    name: 'validate',
    description: 'Validate Architecture layers based on config',
    aliases: ['v']
)]
class ValidateArchitectureCommand extends Command
{
    private bool $failed = false;

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
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $output->writeln("Scanning directory: <comment>$source</comment>");

        $stopwatch = new Stopwatch();
        $stopwatch->start(self::class);
        $scanner = new LayerFilesScanner($config->getArchitecture());
        $scannedLayerFiles = $scanner->scan($source, $ignore);

        foreach ($scannedLayerFiles as $scannedLayerFile) {
            $outputText = "Scanning <slot> <comment>$scannedLayerFile</comment>";
            $validationErrors = $scannedLayerFile->validate();

            if (empty($validationErrors)) {
                $outputText = str_replace('<slot>', '<info>[OK]</info>', $outputText);
                $output->writeln($outputText);
                continue;
            }

            $this->failed = true;
            $outputText = str_replace('<slot>', '<fg=red;options=bold>[ERROR]</>', $outputText);
            $output->writeln($outputText);
            $this->outputValidationErrors($output, $validationErrors);
        }

        $event = $stopwatch->stop(self::class);
        $memoryUsed = $event->getMemory() / (1024 * 1024);

        if ($this->failed) {
            $output->writeln("<error>Issues found (time: {$event->getDuration()}ms, memory: {$memoryUsed}MB)</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>No issues found (time: {$event->getDuration()}ms, memory: {$memoryUsed}MB)</info>");

        return Command::SUCCESS;
    }

    /**
     * @param Exception[] $errors
     */
    private function outputValidationErrors(OutputInterface $output, array $errors): void
    {
        foreach ($errors as $error) {
            $output->writeln("<error>{$error->getMessage()}</error>");
        }
    }
}
