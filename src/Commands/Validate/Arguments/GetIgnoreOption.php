<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments;

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
}