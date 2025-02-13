<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate\Renderers;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Xandrw\ArchitectureEnforcer\Commands\Validate\ValidateCommand;
use Xandrw\ArchitectureEnforcer\Domain\LayerFile;

class DefaultRenderer
{
    public function __construct(
        private readonly OutputInterface $output,
        private readonly Stopwatch $stopwatch,
        private readonly string $source,
        private readonly array $ignore,
    )
    {
    }

    /** @param LayerFile[] $layerFiles */
    public function __invoke(array $layerFiles): bool
    {
        $this->output->writeln("Scanning directory: <comment>$this->source</comment>");
        $this->outputIgnored($this->ignore);

        $hasValidationFailure = false;
        $successfulCount = 0;
        $failedCount = 0;
        $issueCount = 0;
        $totalCount = count($layerFiles);

        foreach ($layerFiles as $layerFile) {
            $scanningText = "Scanning <slot> <comment>$layerFile</comment>";
            $validationIssues = $layerFile->validate();

            if (empty($validationIssues)) {
                $scanningText = str_replace('<slot>', '<info>[OK]</info>', $scanningText);
                $this->output->writeln($scanningText);
                $successfulCount++;
                continue;
            }

            $failedCount++;
            $issueCount += count($validationIssues);
            $hasValidationFailure = true;
            $scanningText = str_replace('<slot>', '<fg=red;options=bold>[ERROR]</>', $scanningText);
            $this->output->writeln($scanningText);
            $this->outputValidationErrors($validationIssues);
        }

        $totalText = "[Scanned: <comment>$totalCount</comment>]";
        $successfulText = "[Successful: <info>$successfulCount</info>]";

        $event = $this->stopwatch->stop(ValidateCommand::class);
        $memoryUsed = $event->getMemory() / (1024 * 1024);
        $timeMemoryText =
            "[Time: <comment>{$event->getDuration()}ms</comment>] [Memory: <comment>{$memoryUsed}MB</comment>]";

        if ($hasValidationFailure) {
            $errorColor = $failedCount > 0 ? 'red' : 'black';
            $failedText = "[Failed: <fg=$errorColor;options=bold>$failedCount</>]";
            $issuesText = "[Issues: <fg=$errorColor;options=bold>$issueCount</>]";
            $this->output->writeln("<fg=red;options=bold>Issues found</>");
            $this->output->writeln("$totalText $successfulText $failedText $issuesText");
            $this->output->writeln($timeMemoryText);
            return true;
        }

        $this->output->writeln("<info>No issues found</info>");
        $this->output->writeln("$totalText $successfulText");
        $this->output->writeln($timeMemoryText);

        return false;
    }

    private function outputIgnored(array $ignored): void
    {
        $ignoredText = '';

        foreach ($ignored as $directory) {
            $ignoredText .= "[<fg=gray>$directory</>] ";
        }

        if (empty($ignoredText)) return;

        $this->output->writeln("Ignored: $ignoredText");
    }

    /**
     * @param Exception[] $errors
     */
    private function outputValidationErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->output->writeln("<fg=red;options=bold>{$error->getMessage()}</>");
        }
    }
}
