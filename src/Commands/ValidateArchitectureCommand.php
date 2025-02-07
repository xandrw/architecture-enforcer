<?php

namespace Xandrw\ArchitectureEnforcer\Commands;

use Exception;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Yaml\Yaml;
use Xandrw\ArchitectureEnforcer\Architecture;
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
        $configPath = $input->getArgument('config');

        if (is_file($configPath) === false) {
            $output->writeln("<error>'$configPath' is not a file</error>");
            return Command::FAILURE;
        }

        $config = $this->getConfig($configPath);

        try {
            $architecture = new Architecture($config);
        } catch (LogicException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $source = $input->getArgument('source');
        $ignore = $this->getIgnoredPaths($input, $config);

        if (is_dir($source) === false) {
            $output->writeln("<error>'$source' is not a valid directory</error>");
            return Command::FAILURE;
        }

        if (in_array($source, $ignore)) {
            $output->writeln("<error>Source '$source' exists in the ignored list</error>");
            return Command::FAILURE;
        }

        $output->writeln("Scanning directory: <comment>$source</comment>");

        $stopwatch = new Stopwatch();
        $stopwatch->start(self::class);
        $scanner = new LayerFilesScanner($architecture);
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
        if (empty($errors)) return;

        foreach ($errors as $error) {
            $output->writeln("<error>{$error->getMessage()}</error>");
        }
    }

    private function getConfig(string $configPath): array
    {
        $extension = strtolower(pathinfo($configPath, PATHINFO_EXTENSION));

        if (in_array($extension, ['yml', 'yaml'], true)) {
            return (array) Yaml::parseFile($configPath);
        }

        if ($extension === 'php') return require $configPath;

        throw new InvalidArgumentException("Unsupported config file extension: $extension");
    }

    private function getIgnoredPaths(InputInterface $input, array $config): array
    {
        $ignoreOptionValue = $input->getOption('ignore');

        if (is_string($ignoreOptionValue)) {
            $ignoreOptionValue = explode(',', $ignoreOptionValue);
        }

        return array_unique([...$ignoreOptionValue ?? [], ...$config['ignore'] ?? []]);
    }
}
