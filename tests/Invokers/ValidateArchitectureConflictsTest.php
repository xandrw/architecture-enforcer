<?php

namespace Xandrw\ArchitectureEnforcer\Tests\Invokers;

use LogicException;
use PHPUnit\Framework\TestCase;
use Xandrw\ArchitectureEnforcer\Invokers\ValidateArchitectureConflicts;

class ValidateArchitectureConflictsTest extends TestCase
{
    /**
     * @test
     * @dataProvider getLayerValidationCases
     */
    public function invoke(array $architectureConfig, bool $fail): void
    {
        if ($fail) $this->expectException(LogicException::class);

        (new ValidateArchitectureConflicts())($architectureConfig);

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
