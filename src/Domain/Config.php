<?php

namespace Xandrw\ArchitectureEnforcer\Domain;

use Symfony\Component\Console\Exception\LogicException;
use Xandrw\ArchitectureEnforcer\Domain\Invokers\ValidateArchitectureConfigConflicts;

class Config
{
    private string $projectRootNamespace;
    private Architecture $architecture;

    public function __construct(private readonly array $config, private array $ignore = [])
    {
        if (!array_key_exists('projectRootNamespace', $this->config)) {
            throw new LogicException("'projectRootNamespace' key not set in config file");
        }

        if (!is_string($this->config['projectRootNamespace']) || empty($this->config['projectRootNamespace'])) {
            throw new LogicException("'projectRootNamespace' key must be a non-empty string");
        }

        if (!array_key_exists('architecture', $this->config)) {
            throw new LogicException("'architecture' key not set in config file");
        }

        if (!is_array($this->config['architecture'])) {
            throw new LogicException("'architecture' key must be an array in config file");
        }

        (new ValidateArchitectureConfigConflicts())($this->config['architecture']);

        $this->projectRootNamespace = $this->config['projectRootNamespace'];
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

    public function getProjectRootNamespace(): string
    {
        return $this->projectRootNamespace;
    }
}
