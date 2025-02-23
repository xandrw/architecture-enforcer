<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Xandrw\ArchitectureEnforcer\Commands\Validate\ValidateCommand;

class GetSourceArgument
{
    public static function addTo(ValidateCommand $command): void
    {
        $command->addArgument(name: 'source', mode: InputArgument::REQUIRED, description: 'Path to app files');
    }

    public function __invoke(InputInterface $input, array $ignore): string
    {
        $source = $input->getArgument('source');

        if (!is_dir($source)) {
            throw new InvalidArgumentException("'$source' is not a valid directory");
        }

        if (in_array($source, $ignore)) {
            throw new InvalidArgumentException("Source '$source' exists in the ignored list");
        }

        foreach ($ignore as $directory) {
            if (str_ends_with($source, $directory) || str_ends_with($directory, $source)) {
                throw new InvalidArgumentException("Source '$source' exists in the ignored list");
            }
        }

        return $source;
    }
}
