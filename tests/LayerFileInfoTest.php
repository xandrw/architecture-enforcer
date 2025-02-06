<?php

namespace Xandrw\ArchitectureEnforcer\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use Xandrw\ArchitectureEnforcer\Exceptions\ArchitectureException;
use Xandrw\ArchitectureEnforcer\LayerFileInfo;

class LayerFileInfoTest extends TestCase
{
    /** @test */
    public function getFileName(): void
    {
        $expectedFileName = 'test.php';
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getFilename')->willReturn($expectedFileName);
        $layerFileInfo = new LayerFileInfo($fileInfoMock, []);

        $this->assertSame($expectedFileName, $layerFileInfo->getFileName());
    }

    /** @test */
    public function getLayerAndNamespace(): void
    {
        $fileContents = '<?php namespace Test\\Layer\\Path;';
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $layerFileInfo = new LayerFileInfo($fileInfoMock, ['Test\\Layer' => []]);

        $this->assertSame('Test\\Layer\\Path', $layerFileInfo->getNamespace());
        $this->assertSame('Test\\Layer', $layerFileInfo->getLayer());
    }

    /** @test */
    public function getNullLayerAndNamespace(): void
    {
        $fileContents = '<?php';
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $layerFileInfo = new LayerFileInfo($fileInfoMock, []);

        $this->assertNull($layerFileInfo->getNamespace());
        $this->assertNull($layerFileInfo->getLayer());
    }

    /** @test */
    public function getUseStatementsWithLines(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA\Path;
            use Test\LayerA\StatementA;
            use Test\LayerB\StatementB;
            $className = \Test\LayerC\StatementC::class;
        PHP;
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $layerFileInfo = new LayerFileInfo($fileInfoMock, []);
        $useStatements = $layerFileInfo->getUseStatementsWithLines();

        $this->assertCount(3, $useStatements);

        foreach ($useStatements as [$useStatement, $line]) {
            $this->assertStringContainsString($useStatement, $fileContents);
        }
    }

    /** @test */
    public function getEmptyUseStatements(): void
    {
        $fileContents = '<?php namespace Test\LayerA\Path;';
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $layerFileInfo = new LayerFileInfo($fileInfoMock, []);

        $this->assertEmpty($layerFileInfo->getUseStatementsWithLines());
    }

    /** @test */
    public function validateReturnsErrorIfUsedLayerNotInChildren(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Test\LayerA\StatementA;
            use Test\LayerB\StatementB;
            $className = \Test\LayerC\StatementC::class;
        PHP;
        $architecture = [
            'Test\\LayerA' => ['Test\\LayerB'],
            'Test\\LayerB' => [],
            'Test\\LayerC' => [],
        ];
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFileInfo($fileInfoMock, $architecture))->validate();

        $this->assertCount(1, $validationErrors);
        $this->assertTrue($validationErrors[0] instanceof ArchitectureException);
    }

    /** @test */
    public function validateReturnsErrorIfStrictAndUsedLayerNotInChildren(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Test\LayerA\StatementA;
            use Test\LayerB\StatementB;
        PHP;
        $architecture = [
            'Test\\LayerA' => ['Test\\LayerA'],
            'Test\\LayerB' => [],
        ];
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFileInfo($fileInfoMock, $architecture))->validate();

        $this->assertCount(1, $validationErrors);
        $this->assertTrue($validationErrors[0] instanceof ArchitectureException);
    }

    /** @test */
    public function validateSuccessIfStrictAndUsedLayerSameAsCurrent(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Test\LayerA\StatementA;
        PHP;
        $architecture = ['Test\\LayerA' => ['Test\\LayerA']];
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFileInfo($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function validateSuccessIfNotStrictAndUsedLayerOutsideOfArchitecture(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Some\Other\Location\StatementA;
        PHP;
        $architecture = ['Test\\LayerA' => []];
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFileInfo($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function validateSuccessIfStrictAndUsedLayerPhpCore(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Test\LayerA\StatementA;
            $className = \SplFileInfo::class;
        PHP;
        $architecture = ['Test\\LayerA' => ['Test\\LayerA']];
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFileInfo($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function validateSuccessIfStrictAndUsedLayerNotInArchitectureButInChildLayers(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Some\Other\Layer\Namespace;
        PHP;
        $architecture = ['Test\\LayerA' => ['Test\\LayerA', 'Some']];
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFileInfo($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function validateSuccessIfNoNamespace(): void
    {
        $fileContents = <<<'PHP'
            <?php
            use Test\LayerA\StatementA;
            use Test\LayerB\StatementB;
            $className = \Test\LayerC\StatementC::class;
        PHP;
        $architecture = [
            'Test\\LayerA' => ['Test\\LayerB'],
            'Test\\LayerB' => [],
            'Test\\LayerC' => [],
        ];
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFileInfo($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function validateSuccessIfNoUseStatements(): void
    {
        $fileContents = '<?php namespace Test\LayerA;';
        $architecture = [
            'Test\\LayerA' => ['Test\\LayerB'],
            'Test\\LayerB' => [],
            'Test\\LayerC' => [],
        ];
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFileInfo($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }
}
