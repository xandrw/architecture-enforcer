<?php

namespace Xandrw\ArchitectureEnforcer\Tests\Unit\Commands\Validate\Arguments;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Xandrw\ArchitectureEnforcer\Commands\Validate\Arguments\NoCircularOption;
use Xandrw\ArchitectureEnforcer\Domain\Architecture;

class NoCircularOptionTest extends TestCase
{
    /** @test */
    public function failIfCircularOptionSetAndLayersHaveCircularDependencies(): void
    {
        $this->expectException(LogicException::class);

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->expects($this->once())->method('getOption')->with('no-circular')->willReturn(true);
        $architecture = new Architecture([
            'Test\\LayerA' => ['Test\\LayerB'],
            'Test\\LayerB' => ['Test\\LayerA'],
        ]);
        (new NoCircularOption())($inputMock, $architecture);
    }

    /** @test */
    public function successIfCircularOptionSetAndLayersAreNonCircular(): void
    {
        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->expects($this->once())->method('getOption')->with('no-circular')->willReturn(true);
        $architecture = new Architecture([
            'Test\\LayerA' => ['Test\\LayerB'],
            'Test\\LayerB' => ['Test\\LayerC'],
            'Test\\LayerC' => ['Test\\LayerA'],
        ]);
        (new NoCircularOption())($inputMock, $architecture);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function successIfCircularOptionNotSetAndLayersHaveCircularDependencies(): void
    {
        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->expects($this->once())->method('getOption')->with('no-circular')->willReturn(false);
        $architecture = new Architecture([
            'Test\\LayerA' => ['Test\\LayerB'],
            'Test\\LayerB' => ['Test\\LayerA'],
        ]);
        (new NoCircularOption())($inputMock, $architecture);

        $this->addToAssertionCount(1);
    }
}
