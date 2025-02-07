<?php

namespace Xandrw\ArchitectureEnforcer\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Xandrw\ArchitectureEnforcer\Architecture;

class ArchitectureTest extends TestCase
{
    /** @test */
    public function instantiateWithoutArchitectureConfigKey(): void
    {
        $this->expectException(LogicException::class);
        new Architecture([]);
    }

    /** @test */
    public function instantiateWithNonArrayArchitecture(): void
    {
        $this->expectException(LogicException::class);
        new Architecture(['architecture' => null]);
    }

    /** @test */
    public function getLayerByNamespaceReturnsNullIfNamespaceIsNull(): void
    {
        $architecture = new Architecture(['architecture' => []]);

        $this->assertNull($architecture->getLayerByNamespace(null));
    }

    /** @test */
    public function getLayerByNamespaceReturnsNullIfNotDefined(): void
    {
        $architecture = new Architecture(['architecture' => []]);

        $this->assertNull($architecture->getLayerByNamespace('Test\\Namespace'));
    }

    /** @test */
    public function getLayerByNamespacePartial(): void
    {
        $architecture = new Architecture([
            'architecture' => ['Test' => []],
        ]);

        $this->assertSame('Test', (string) $architecture->getLayerByNamespace('Test\\Namespace'));
    }

    /** @test */
    public function getLayerByNamespaceFull(): void
    {
        $architecture = new Architecture([
            'architecture' => ['Test\\Namespace' => []],
        ]);

        $this->assertSame('Test\\Namespace', (string) $architecture->getLayerByNamespace('Test\\Namespace'));
    }

    /** @test */
    public function doesNotHaveLayer(): void
    {
        $architecture = new Architecture(['architecture' => []]);

        $this->assertFalse($architecture->hasLayer('Test\\Namespace'));
    }

    /** @test */
    public function hasLayer(): void
    {
        $architecture = new Architecture([
            'architecture' => ['Test\\Namespace' => []],
        ]);

        $this->assertTrue($architecture->hasLayer('Test\\Namespace'));
    }
}
