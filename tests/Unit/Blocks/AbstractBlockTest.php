<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Blocks;

use FCMS\Blocks\AbstractBlock;
use FCMS\Blocks\BlockInterface;
use PHPUnit\Framework\TestCase;

class AbstractBlockTest extends TestCase
{
    private BlockInterface $block;

    protected function setUp(): void
    {
        parent::setUp();

        $this->block = new class extends AbstractBlock {
            public function getMetadata(): array {
                return ['name' => 'test', 'title' => 'Test Block'];
            }

            public function render(array $attributes, string $content = ''): string {
                $attrs = $this->mergeAttributes($attributes);
                return '<div>' . $this->escape($attrs['text'] ?? '') . '</div>';
            }

            public function renderEditor(array $attributes, string $content = ''): string {
                return '<input type="text" value="' . $this->escape($attributes['text'] ?? '') . '">';
            }

            public function getDefaultAttributes(): array {
                return ['text' => '', 'class' => 'default'];
            }
        };
    }

    public function testValidateReturnsTrueForValidAttributes(): void
    {
        $result = $this->block->validate(['text' => 'Hello', 'class' => 'custom']);
        $this->assertTrue($result);
    }

    public function testValidateReturnsTrueForMissingOptionalAttributes(): void
    {
        $result = $this->block->validate([]);
        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseForWrongType(): void
    {
        $result = $this->block->validate(['text' => 123]); // Should be string
        $this->assertFalse($result);
    }

    public function testMergeAttributesMergesWithDefaults(): void
    {
        $output = $this->block->render(['text' => 'Hello']);
        $this->assertEquals('<div>Hello</div>', $output);
    }

    public function testEscapeEscapesHtml(): void
    {
        $output = $this->block->render(['text' => '<script>alert("xss")</script>']);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testRenderAttributesWithStringValues(): void
    {
        $testBlock = new class extends AbstractBlock {
            public function getMetadata(): array { return []; }
            public function render(array $attributes, string $content = ''): string {
                return $this->renderAttributes(['class' => 'test', 'id' => 'my-id']);
            }
            public function renderEditor(array $attributes, string $content = ''): string { return ''; }
            public function getDefaultAttributes(): array { return []; }
        };

        $output = $testBlock->render([]);
        $this->assertStringContainsString('class="test"', $output);
        $this->assertStringContainsString('id="my-id"', $output);
    }

    public function testRenderAttributesWithBooleanTrue(): void
    {
        $testBlock = new class extends AbstractBlock {
            public function getMetadata(): array { return []; }
            public function render(array $attributes, string $content = ''): string {
                return $this->renderAttributes(['disabled' => true]);
            }
            public function renderEditor(array $attributes, string $content = ''): string { return ''; }
            public function getDefaultAttributes(): array { return []; }
        };

        $output = $testBlock->render([]);
        $this->assertStringContainsString('disabled', $output);
        $this->assertStringNotContainsString('disabled="', $output);
    }

    public function testRenderAttributesSkipsNullAndFalse(): void
    {
        $testBlock = new class extends AbstractBlock {
            public function getMetadata(): array { return []; }
            public function render(array $attributes, string $content = ''): string {
                return $this->renderAttributes(['skip1' => null, 'skip2' => false, 'show' => 'yes']);
            }
            public function renderEditor(array $attributes, string $content = ''): string { return ''; }
            public function getDefaultAttributes(): array { return []; }
        };

        $output = $testBlock->render([]);
        $this->assertStringNotContainsString('skip1', $output);
        $this->assertStringNotContainsString('skip2', $output);
        $this->assertStringContainsString('show="yes"', $output);
    }
}
