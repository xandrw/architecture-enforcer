<?php

namespace Xandrw\ArchitectureEnforcer\Tests;

use PHPUnit\Framework\TestCase;
use Xandrw\ArchitectureEnforcer\Layer;

class LayerTest extends TestCase
{
    /** @test */
    public function notStrictIfNotInChildren(): void
    {
        $layer = new Layer('Test', []);
        $this->assertFalse($layer->isStrict());
    }

    /** @test */
    public function strictIfNotInChildren(): void
    {
        $layer = new Layer('Test', ['Test']);
        $this->assertTrue($layer->isStrict());
    }

    /** @test */
    public function hasChildLayerReturnsFalseIfNamespaceIsNull(): void
    {
        $layer = new Layer('Test', []);
        $this->assertFalse($layer->hasChildLayer(null));
    }

    /** @test */
    public function hasChildLayerReturnsFalseIfNamespaceIsNotDefined(): void
    {
        $layer = new Layer('Test', []);
        $this->assertFalse($layer->hasChildLayer('Test'));
    }

    /** @test */
    public function hasChildLayerReturnsTruePartial(): void
    {
        $layer = new Layer('Test', ['Test']);
        $this->assertTrue($layer->hasChildLayer('Test\\Namespace'));
    }

    /** @test */
    public function hasChildLayerReturnsTrueFull(): void
    {
        $layer = new Layer('Test', ['Test\\Namespace']);
        $this->assertTrue($layer->hasChildLayer('Test\\Namespace'));
    }

    /** @test */
    public function toStringReturnsLayerName(): void
    {
        $layer = new Layer('Test', []);
        $this->assertSame('Test', (string) $layer);
    }
}
