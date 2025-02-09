<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Xandrw\ArchitectureEnforcer\Domain\LayerFilesScanner;

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
            $output->writeln("<fg=red;options=bold>{$e->getMessage()}</>");
            return Command::FAILURE;
        }

        $output->writeln("Scanning directory: <comment>$source</comment>");
        $this->outputIgnored($output, $ignore);

        $stopwatch = new Stopwatch();
        $stopwatch->start(self::class);
        $scanner = new LayerFilesScanner($config->getArchitecture());
        $scannedLayerFiles = $scanner->scan($source, $ignore);

        $successfulCount = 0;
        $failedCount = 0;
        $issueCount = 0;
        $totalCount = count($scannedLayerFiles);

        foreach ($scannedLayerFiles as $scannedLayerFile) {
            $scanningText = "Scanning <slot> <comment>$scannedLayerFile</comment>";
            $validationErrors = $scannedLayerFile->validate();

            if (empty($validationErrors)) {
                $scanningText = str_replace('<slot>', '<info>[OK]</info>', $scanningText);
                $output->writeln($scanningText);
                $successfulCount++;
                continue;
            }

            $failedCount++;
            $issueCount += count($validationErrors);
            $this->failed = true;
            $scanningText = str_replace('<slot>', '<fg=red;options=bold>[ERROR]</>', $scanningText);
            $output->writeln($scanningText);
            $this->outputValidationErrors($output, $validationErrors);
        }

        $event = $stopwatch->stop(self::class);
        $memoryUsed = $event->getMemory() / (1024 * 1024);
        $totalText = "[Scanned: <comment>$totalCount</comment>]";
        $successfulText = "[Successful: <info>$successfulCount</info>]";
        $errorColor = $failedCount > 0 ? 'red' : 'black';
        $failedText = "[Failed: <fg=$errorColor;options=bold>$failedCount</>]";
        $issuesText = "[Issues: <fg=$errorColor;options=bold>$issueCount</>]";
        $timeMemoryText =
            "[Time: <comment>{$event->getDuration()}ms</comment>] [Memory: <comment>{$memoryUsed}MB</comment>]";

        if ($this->failed) {
            $output->writeln("$totalText $successfulText $failedText $issuesText");
            $output->writeln($timeMemoryText);
            $output->writeln("<fg=red;options=bold>Issues found</>");
            return Command::FAILURE;
        }

        $output->writeln("$totalText $successfulText");
        $output->writeln($timeMemoryText);
        $output->writeln("<info>No issues found</info>");

        return Command::SUCCESS;
    }

    /**
     * @param Exception[] $errors
     */
    private function outputValidationErrors(OutputInterface $output, array $errors): void
    {
        foreach ($errors as $error) {
            $output->writeln("<fg=red;options=bold>{$error->getMessage()}</>");
        }
    }

    private function outputIgnored(OutputInterface $output, array $ignored): void
    {
        $ignoredText = '';

        foreach ($ignored as $directory) {
            $ignoredText .= "[<fg=gray>$directory</>] ";
        }

        $output->writeln("Ignored: $ignoredText");
    }
}
