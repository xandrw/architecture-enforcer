<?php

namespace Xandrw\ArchitectureEnforcer\Domain;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Yaml\Yaml;
use Xandrw\ArchitectureEnforcer\Domain\Invokers\ValidateArchitectureConfigConflicts;

class Config
{
    private array $config = [];
    private array $ignore = [];
    private Architecture $architecture;

    public function __construct(string $configPath)
    {
        if (!is_file($configPath)) {
            throw new LogicException("'$configPath' is not a file");
        }

        $extension = strtolower(pathinfo($configPath, PATHINFO_EXTENSION));

        $this->config = match ($extension) {
            'php' => require $configPath,
            'yml', 'yaml' => (array) Yaml::parseFile($configPath),
            default => throw new InvalidArgumentException("Unsupported config file extension: $extension"),
        };

        if (!array_key_exists('architecture', $this->config)) {
            throw new LogicException("'architecture' key not set in config file");
        }

        if (!is_array($this->config['architecture'])) {
            throw new LogicException("'architecture' key must be an array in config file");
        }

        (new ValidateArchitectureConfigConflicts())($this->config['architecture']);

        $this->architecture = new Architecture($this->config['architecture']);
        $this->ignore = $this->config['ignore'] ?? [];
    }

    public function getArchitecture(): Architecture
    {
        return $this->architecture;
    }

    public function getIgnore(): array
    {
        return $this->ignore;
    }
}