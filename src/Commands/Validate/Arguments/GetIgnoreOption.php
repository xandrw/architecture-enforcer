<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class GetIgnoreOption
{
    public function __invoke(InputInterface $input, array $configDefaults): array
    {
        $ignoreOptionValue = $input->getOption('ignore');

        if (is_string($ignoreOptionValue)) {
            $ignoreOptionValue = explode(',', $ignoreOptionValue);
        }

        return array_unique([...$ignoreOptionValue ?? [], ...$configDefaults]);
    }

    public static function addTo(Command $command): void
    {
        $command->addOption(
            name: 'ignore',
            shortcut: 'i',
            mode: InputArgument::IS_ARRAY,
            description: 'Comma-separated list of ignored paths from the current directory (e.g.: vendor,var,tests)',
        );
    }
}
