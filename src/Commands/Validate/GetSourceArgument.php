<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;

class GetSourceArgument
{
    public function __invoke(InputInterface $input, array $ignore): string
    {
        $source = $input->getArgument('source');

        if (!is_dir($source)) {
            throw new LogicException("'$source' is not a valid directory");
        }

        if (in_array($source, $ignore)) {
            throw new LogicException("Source '$source' exists in the ignored list");
        }

        foreach ($ignore as $directory) {
            if (str_ends_with($source, $directory) || str_ends_with($directory, $source)) {
                throw new LogicException("Source '$source' exists in the ignored list");
            }
        }

        return $source;
    }
}