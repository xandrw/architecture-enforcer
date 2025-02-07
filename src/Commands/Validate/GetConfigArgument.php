<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate;

use Symfony\Component\Console\Input\InputInterface;
use Xandrw\ArchitectureEnforcer\Config;

class GetConfigArgument
{
    public function __invoke(InputInterface $input): Config
    {
        $configPath = $input->getArgument('config');

        return new Config($configPath);
    }
}