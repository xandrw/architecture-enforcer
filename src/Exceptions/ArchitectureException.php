<?php

namespace Xandrw\ArchitectureEnforcer\Exceptions;

use Exception;

class ArchitectureException extends Exception
{
    public function __construct(protected string $file, protected int $line, protected string $useStatement)
    {
        parent::__construct("$file:$line cannot use $useStatement");
    }
}
