<?php

namespace Xandrw\ArchitectureEnforcer\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use Xandrw\ArchitectureEnforcer\Architecture;
use Xandrw\ArchitectureEnforcer\Exceptions\ArchitectureException;
use Xandrw\ArchitectureEnforcer\LayerFile;

class LayerFileTest extends TestCase
{
    /** @test */
    public function initializeWithNullNamespaceAndLayer(): void
    {
        $fileContents = '<?php';
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $layerFileInfo = new LayerFile($fileInfoMock, new Architecture([]));

        $this->assertNull($layerFileInfo->namespace);
        $this->assertNull($layerFileInfo->layer);
    }

    /** @test */
    public function initializeWithNullLayerIfNamespaceNotDefinedInArchitecture(): void
    {
        $fileContents = '<?php namespace Test\Namespace;';
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $layerFileInfo = new LayerFile($fileInfoMock, new Architecture([]));

        $this->assertSame('Test\\Namespace', $layerFileInfo->namespace);
        $this->assertNull($layerFileInfo->layer);
    }

    /** @test */
    public function initializeWithNamespaceAndLayer(): void
    {
        $fileContents = '<?php namespace Test\Namespace;';
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $layerFileInfo = new LayerFile($fileInfoMock, new Architecture(['Test' => []]));

        $this->assertSame('Test\\Namespace', $layerFileInfo->namespace);
        $this->assertSame('Test', (string) $layerFileInfo->layer);
    }

    /** @test */
    public function getFileName(): void
    {
        $fileName = 'test.php';
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getFilename')->willReturn($fileName);
        $layerFileInfo = new LayerFile($fileInfoMock, new Architecture([]));

        $this->assertSame($fileName, $layerFileInfo->getFileName());
    }

    /** @test */
    public function errorIfUsedNamespaceNotInSelfOrInArchitecture(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Test\LayerA\StatementA;
            use Test\LayerB\StatementB;
            $className = \Test\LayerC\StatementC::class;
        PHP;
        $architectureConfig = [
            'Test\\LayerA' => ['Test\\LayerB'],
            'Test\\LayerB' => [],
            'Test\\LayerC' => [],
        ];
        $architecture = new Architecture($architectureConfig);
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);

        $validationErrors = (new LayerFile($fileInfoMock, $architecture))->validate();

        $this->assertCount(1, $validationErrors);
        $this->assertTrue($validationErrors[0] instanceof ArchitectureException);
    }

    /** @test */
    public function errorIfStrictAndUsedNamespaceNotInChildLayers(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Test\LayerA\StatementA;
            use Test\LayerB\StatementB;
        PHP;
        $architectureConfig = [
            'Test\\LayerA' => ['Test\\LayerA'],
            'Test\\LayerB' => [],
        ];
        $architecture = new Architecture($architectureConfig);
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFile($fileInfoMock, $architecture))->validate();

        $this->assertCount(1, $validationErrors);
        $this->assertTrue($validationErrors[0] instanceof ArchitectureException);
    }

    /** @test */
    public function successIfStrictAndUsedNamespaceIsCurrentLayer(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Test\LayerA\StatementA;
        PHP;
        $architectureConfig = ['Test\\LayerA' => ['Test\\LayerA']];
        $architecture = new Architecture($architectureConfig);
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFile($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function successIfNotStrictAndUsedNamespaceOutsideOfArchitecture(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Some\Other\Location\StatementA;
        PHP;
        $architectureConfig = ['Test\\LayerA' => []];
        $architecture = new Architecture($architectureConfig);
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFile($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function successIfStrictAndUsedNamespaceIsPhpCore(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use Test\LayerA\StatementA;
            $className = \SplFileInfo::class;
        PHP;
        $architectureConfig = ['Test\\LayerA' => ['Test\\LayerA']];
        $architecture = new Architecture($architectureConfig);
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFile($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function successIfStrictAndUsedNamespaceNotInArchitectureButInChildLayers(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use External\Other\Layer\Namespace;
        PHP;
        $architectureConfig = ['Test\\LayerA' => ['Test\\LayerA', 'External']];
        $architecture = new Architecture($architectureConfig);
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFile($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function successIfStrictAndUsedNamespaceHasAliasAndIsInChildLayers(): void
    {
        $fileContents = <<<'PHP'
            <?php
            namespace Test\LayerA;
            use External\Layer\Namespace as ELN;
            $className = ELN\SubClass::class;
        PHP;
        $architectureConfig = ['Test\\LayerA' => ['Test\\LayerA', 'External', 'ELN']];
        $architecture = new Architecture($architectureConfig);
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFile($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function successIfNoNamespace(): void
    {
        $fileContents = <<<'PHP'
            <?php
            use Test\LayerA\StatementA;
            use Test\LayerB\StatementB;
            $className = \Test\LayerC\StatementC::class;
        PHP;
        $architectureConfig = [
            'Test\\LayerA' => ['Test\\LayerB'],
            'Test\\LayerB' => [],
            'Test\\LayerC' => [],
        ];
        $architecture = new Architecture($architectureConfig);
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFile($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function successIfNoUseStatements(): void
    {
        $fileContents = '<?php namespace Test\LayerA;';
        $architectureConfig = [
            'Test\\LayerA' => ['Test\\LayerB'],
            'Test\\LayerB' => [],
            'Test\\LayerC' => [],
        ];
        $architecture = new Architecture($architectureConfig);
        $fileInfoMock = $this->createMock(SplFileInfo::class);
        $fileInfoMock->expects($this->once())->method('getContents')->willReturn($fileContents);
        $validationErrors = (new LayerFile($fileInfoMock, $architecture))->validate();

        $this->assertEmpty($validationErrors);
    }
}
