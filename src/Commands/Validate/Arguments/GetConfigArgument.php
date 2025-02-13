<?php

namespace Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments;

use JsonException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;
use Xandrw\ArchitectureEnforcer\Domain\Config;

class GetConfigArgument
{
    /**
     * @throws JsonException
     */
    public function __invoke(InputInterface $input): Config
    {
        $configPath = $input->getArgument('config');

        if (!is_file($configPath)) {
            throw new InvalidArgumentException("'$configPath' is not a file");
        }

        $extension = strtolower(pathinfo($configPath, PATHINFO_EXTENSION));

        /** @var array $config */
        $config = match ($extension) {
            'php' => require $configPath,
            'yml', 'yaml' => (array) Yaml::parseFile($configPath),
            'json' => json_decode(json: file_get_contents($configPath), associative: true, flags: JSON_THROW_ON_ERROR),
            default => throw new InvalidArgumentException("Unsupported config file extension: $extension"),
        };

        if (!is_array($config)) {
            throw new LogicException("'$configPath' does not return an array");
        }

        return new Config($config);
    }
}
