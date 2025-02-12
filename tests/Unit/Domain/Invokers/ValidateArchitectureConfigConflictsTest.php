<?php

namespace Xandrw\ArchitectureEnforcer\Tests\Unit\Domain\Invokers;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\LogicException;
use Xandrw\ArchitectureEnforcer\Domain\Invokers\ValidateArchitectureConfigConflicts;

class ValidateArchitectureConfigConflictsTest extends TestCase
{
    /**
     * @test
     * @dataProvider getLayerValidationCases
     */
    public function invoke(array $architectureConfig, bool $fail): void
    {
        if ($fail) $this->expectException(LogicException::class);

        (new ValidateArchitectureConfigConflicts())($architectureConfig);

        $this->addToAssertionCount(1);
    }

    private function getLayerValidationCases(): array
    {
        return [
            'conflictSubNamespace' => [
                [
                    'Test\\Layer\\Namespace' => [],
                    'Test\\Layer\\Namespace\\SubNamespace' => [],
                ],
                true,
            ],
            'conflictSubNamespaceReversed' => [
                [
                    'Test\\Layer\\Namespace\\SubNamespace' => [],
                    'Test\\Layer\\Namespace' => [],
                ],
                true,
            ],
            'conflictComplexMultiLayer' => [
                [
                    'Test' => [],
                    'Test\\A' => [],
                    'AnotherTest' => [],
                ],
                true,
            ],
            'nonConflictSubNamespace' => [
                [
                    'Test\\Layer\\Namespace\\SubNamespaceA' => [],
                    'Test\\Layer\\Namespace\\SubNamespaceB' => [],
                ],
                false,
            ],
            'emptyConfiguration' => [
                [],
                false,
            ],
            'singleNamespace' => [
                [
                    'Test\\Layer' => [],
                ],
                false,
            ],
            'multipleUnrelatedNamespaces' => [
                [
                    'App\\Foo' => [],
                    'Lib\\Bar' => [],
                ],
                false,
            ],
        ];
    }
}
