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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Xandrw\ArchitectureEnforcer\ArchitectureException;
use Xandrw\ArchitectureEnforcer\LayerFileInfo;

/** @SuppressUnused */
#[AsCommand(
    name: 'validate',
    description: 'Validate Architecture layers based on config',
    aliases: ['v']
)]
class ValidateArchitectureCommand extends Command
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
        $configArgument = $input->getArgument('config');

        if (is_file($configArgument) === false) {
            $output->writeln("<error>'$configArgument' is not a file</error>");
            return Command::FAILURE;
        }

        $config = $this->getConfig($configArgument);
        $architecture = $config['architecture']
            ?? throw new LogicException('architecture key not set in configuration file');

        $source = $input->getArgument('source');
        $ignore = $this->getIgnoredPaths($input, $config);

        if (in_array($source, $ignore)) {
            $output->writeln("<error>Source '$source' exists in the ignored list</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Scanning directory:</info> <comment>$source</comment>");

        if (is_dir($source) === false) {
            $output->writeln("<error>'$source' is not a valid directory</error>");
            return Command::FAILURE;
        }

        $errors = [];

        foreach ($this->scanFiles($source, $ignore) as $scannedFile) {
            try {
                $validationErrors = (new LayerFileInfo($scannedFile, $architecture))->validate();

                if (!empty($validationErrors)) {
                    $errors = [...$errors, (string) $scannedFile => $validationErrors];
                }

                $output->writeln("<info>Scanned:</info> <comment>$scannedFile</comment>");
            } catch (Exception $e) {
                $output->writeln('<error>Failed with exception</error>');
                $output->writeln("<error>$e</error>");
                return Command::FAILURE;
            }
        }

        if (!empty($errors)) {
            $this->printValidationErrors($errors, $output);
            return Command::FAILURE;
        }

        $output->writeln('<info>No architecture issues found</info>');
        return Command::SUCCESS;
    }

    private function getConfig(string $configPath): array
    {
        $extension = strtolower(pathinfo($configPath, PATHINFO_EXTENSION));

        if (in_array($extension, ['yml', 'yaml'], true)) {
            return (array) Yaml::parseFile($configPath);
        }

        if ($extension === 'php') {
            return require $configPath;
        }
        throw new InvalidArgumentException("Unsupported config file extension: $extension");
    }

    /** @return SplFileInfo[] */
    private function scanFiles(string $directory, array $ignoredPaths): array
    {
        $finder = (new Finder())
            ->files()->in($directory)
            ->exclude($ignoredPaths)
            ->ignoreDotFiles(true)
            ->name('*.php');
        return iterator_to_array($finder);
    }

    private function printValidationErrors(array $errors, OutputInterface $output): void
    {
        foreach ($errors as $fileName => $validationErrors) {
            $output->writeln("<error>Failed:</error> <comment>$fileName</comment>");
            /** @var ArchitectureException $validationError */
            foreach ($validationErrors as $validationError) {
                $output->writeln("<error>{$validationError->getMessage()}</error>");
            }
        }
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
