<?php

namespace FCMS\Blocks;

use FCMS\Blocks\AbstractBlock;

/**
 * Absatz-Block
 */
class ParagraphBlock extends AbstractBlock
{
    public function getMetadata(): array
    {
        return [
            'name' => 'paragraph',
            'title' => 'Absatz',
            'icon' => 'paragraph',
            'category' => 'text',
            'description' => 'Einfacher Textabsatz',
        ];
    }

    public function getDefaultAttributes(): array
    {
        return [
            'content' => '',
            'align' => '',
            'fontSize' => '',
            'textColor' => '',
            'backgroundColor' => '',
            'className' => '',
        ];
    }

    public function render(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);
        // Block-Editor Format: data.text
        $content = $content ?: ($attrs['text'] ?? $attrs['content'] ?? '');

        $htmlAttrs = [];

        if ($attrs['className']) {
            $htmlAttrs['class'] = $attrs['className'];
        }

        if ($attrs['align']) {
            $htmlAttrs['class'] = ($htmlAttrs['class'] ?? '') . ' text-' . $attrs['align'];
        }

        $style = [];
        if ($attrs['fontSize']) {
            $style[] = 'font-size: ' . $attrs['fontSize'];
        }
        if ($attrs['textColor']) {
            $style[] = 'color: ' . $attrs['textColor'];
        }
        if ($attrs['backgroundColor']) {
            $style[] = 'background-color: ' . $attrs['backgroundColor'];
        }

        if (!empty($style)) {
            $htmlAttrs['style'] = implode('; ', $style);
        }

        $attrString = $this->renderAttributes($htmlAttrs);

        return '<p' . ($attrString ? ' ' . $attrString : '') . '>' . $content . '</p>';
    }

    public function renderEditor(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);
        $content = $content ?: $attrs['content'];

        return '<div class="block-editor paragraph-block">
            <textarea class="form-control" rows="3" data-block-content>' .
            $this->escape($content) .
            '</textarea>
        </div>';
    }
}
