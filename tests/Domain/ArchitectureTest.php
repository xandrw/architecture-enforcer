<?php

namespace Xandrw\ArchitectureEnforcer\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Xandrw\ArchitectureEnforcer\Domain\Architecture;

class ArchitectureTest extends TestCase
{
    /** @test */
    public function getLayerByNamespaceReturnsNullIfNamespaceIsNull(): void
    {
        $architecture = new Architecture([]);

        $this->assertNull($architecture->getLayerByNamespace(null));
    }

    /** @test */
    public function getLayerByNamespaceReturnsNullIfNotDefined(): void
    {
        $architecture = new Architecture([]);

        $this->assertNull($architecture->getLayerByNamespace('Test\\Namespace'));
    }

    /** @test */
    public function getLayerByNamespacePartial(): void
    {
        $architecture = new Architecture(['Test' => []]);

        $this->assertSame('Test', (string) $architecture->getLayerByNamespace('Test\\Namespace'));
    }

    /** @test */
    public function getLayerByNamespaceFull(): void
    {
        $architecture = new Architecture(['Test\\Namespace' => []]);

        $this->assertSame('Test\\Namespace', (string) $architecture->getLayerByNamespace('Test\\Namespace'));
    }

    /** @test */
    public function doesNotHaveLayer(): void
    {
        $architecture = new Architecture([]);

        $this->assertFalse($architecture->hasLayer('Test\\Namespace'));
    }

    /** @test */
    public function hasLayer(): void
    {
        $architecture = new Architecture(['Test\\Namespace' => []]);

        $this->assertTrue($architecture->hasLayer('Test\\Namespace'));
    }
}
