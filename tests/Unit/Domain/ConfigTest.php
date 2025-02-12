<?php

namespace Xandrw\ArchitectureEnforcer\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\LogicException;
use Xandrw\ArchitectureEnforcer\Domain\Architecture;
use Xandrw\ArchitectureEnforcer\Domain\Config;

class ConfigTest extends TestCase
{
    /** @test */
    public function successfulInstantiation(): void
    {
        $config = new Config(['architecture' => ['Test\\Namespace' => []], 'ignore' => ['ignored']]);
        $this->assertInstanceOf(Architecture::class, $config->getArchitecture());
        $this->assertTrue($config->getArchitecture()->hasLayer('Test\\Namespace'));
        $this->assertTrue(in_array('ignored', $config->getIgnore()));
    }

    /** @test */
    public function emptyArchitectureAndMissingIgnore(): void
    {
        $config = new Config(['architecture' => []]);
        $this->assertInstanceOf(Architecture::class, $config->getArchitecture());
        $this->assertEmpty($config->getIgnore());
    }

    /** @test */
    public function architectureKeyNotDefined(): void
    {
        $this->expectException(LogicException::class);
        new Config([]);
    }

    /** @test */
    public function architectureKeyNonArray(): void
    {
        $this->expectException(LogicException::class);
        new Config(['architecture' => 'not-an-array']);
    }
}
