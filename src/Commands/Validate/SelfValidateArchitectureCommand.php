<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

/** @SuppressUnused */
#[AsCommand(
        name: 'self-validate',
        description: 'Self validates Architecture layers based on own config',
        aliases: ['s']
)]
class SelfValidateArchitectureCommand extends Command
{
    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = $this->getApplication()->find('validate');
        
        $arguments = [
                'command' => 'validate',
                'source' => './',
                'config' => './config/architecture.php',
        ];

        $arrayInput = new ArrayInput($arguments);
        return $command->run($arrayInput, $output);
    }
}
