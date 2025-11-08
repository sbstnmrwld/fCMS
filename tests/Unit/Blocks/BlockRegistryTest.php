<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Blocks;

use FCMS\Blocks\BlockRegistry;
use FCMS\Blocks\BlockInterface;
use PHPUnit\Framework\TestCase;

class BlockRegistryTest extends TestCase
{
    private BlockRegistry $registry;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/fcms_blocks_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->registry = new BlockRegistry($this->tempDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '/*'));
        rmdir($this->tempDir);
        parent::tearDown();
    }

    private function createMockBlock(string $name = 'test'): BlockInterface
    {
        return new class($name) implements BlockInterface {
            private string $name;
            public function __construct(string $name) { $this->name = $name; }
            public function getMetadata(): array { return ['name' => $this->name, 'title' => 'Test Block']; }
            public function render(array $attributes, string $content = ''): string { return '<div>Test</div>'; }
            public function renderEditor(array $attributes, string $content = ''): string { return '<div>Editor</div>'; }
            public function validate(array $attributes): bool { return true; }
            public function getDefaultAttributes(): array { return []; }
        };
    }

    public function testRegisterAddsBlock(): void
    {
        $block = $this->createMockBlock();
        $this->registry->register('test', $block);

        $this->assertTrue($this->registry->has('test'));
    }

    public function testGetReturnsRegisteredBlock(): void
    {
        $block = $this->createMockBlock();
        $this->registry->register('test', $block);

        $retrieved = $this->registry->get('test');

        $this->assertSame($block, $retrieved);
    }

    public function testGetReturnsNullForUnregisteredBlock(): void
    {
        $this->assertNull($this->registry->get('nonexistent'));
    }

    public function testHasReturnsTrueForRegisteredBlock(): void
    {
        $block = $this->createMockBlock();
        $this->registry->register('test', $block);

        $this->assertTrue($this->registry->has('test'));
    }

    public function testHasReturnsFalseForUnregisteredBlock(): void
    {
        $this->assertFalse($this->registry->has('nonexistent'));
    }

    public function testAllReturnsAllBlocks(): void
    {
        $block1 = $this->createMockBlock('block1');
        $block2 = $this->createMockBlock('block2');

        $this->registry->register('block1', $block1);
        $this->registry->register('block2', $block2);

        $all = $this->registry->all();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('block1', $all);
        $this->assertArrayHasKey('block2', $all);
    }

    public function testGetAllBlocksIsAliasForAll(): void
    {
        $block = $this->createMockBlock();
        $this->registry->register('test', $block);

        $this->assertEquals($this->registry->all(), $this->registry->getAllBlocks());
    }

    public function testGetAllMetadataReturnsMetadataForAllBlocks(): void
    {
        $block1 = $this->createMockBlock('block1');
        $block2 = $this->createMockBlock('block2');

        $this->registry->register('block1', $block1);
        $this->registry->register('block2', $block2);

        $metadata = $this->registry->getAllMetadata();

        $this->assertCount(2, $metadata);
        $this->assertEquals('block1', $metadata['block1']['name']);
        $this->assertEquals('block2', $metadata['block2']['name']);
    }

    public function testRenderBlockRendersRegisteredBlock(): void
    {
        $block = $this->createMockBlock();
        $this->registry->register('test', $block);

        $output = $this->registry->renderBlock('test', []);

        $this->assertEquals('<div>Test</div>', $output);
    }

    public function testRenderBlockReturnsCommentForUnregisteredBlock(): void
    {
        $output = $this->registry->renderBlock('nonexistent', []);

        $this->assertStringContainsString('<!-- Block "nonexistent" nicht gefunden -->', $output);
    }

    public function testRenderBlockCatchesExceptions(): void
    {
        $block = new class implements BlockInterface {
            public function getMetadata(): array { return ['name' => 'error']; }
            public function render(array $attributes, string $content = ''): string {
                throw new \Exception('Test error');
            }
            public function renderEditor(array $attributes, string $content = ''): string { return ''; }
            public function validate(array $attributes): bool { return true; }
            public function getDefaultAttributes(): array { return []; }
        };

        $this->registry->register('error', $block);
        $output = $this->registry->renderBlock('error', []);

        $this->assertStringContainsString('<!-- Fehler beim Rendern von Block "error"', $output);
        $this->assertStringContainsString('Test error', $output);
    }

    public function testAutoDiscoverBlocksDoesNotThrow(): void
    {
        // Test dass autoDiscoverBlocks ohne Fehler läuft
        // Tatsächliche Block-Discovery ist schwer zu testen da require_once
        $this->expectNotToPerformAssertions();
        $this->registry->autoDiscoverBlocks();
    }
}
