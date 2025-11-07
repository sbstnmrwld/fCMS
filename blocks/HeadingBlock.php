<?php

namespace FCMS\Blocks\Types;

use FCMS\Blocks\AbstractBlock;

/**
 * Überschrift-Block
 */
class HeadingBlock extends AbstractBlock
{
    public function getMetadata(): array
    {
        return [
            'name' => 'heading',
            'title' => 'Überschrift',
            'icon' => 'heading',
            'category' => 'text',
            'description' => 'Überschrift (H1-H6)',
        ];
    }

    public function getDefaultAttributes(): array
    {
        return [
            'content' => '',
            'level' => 2,
            'align' => '',
            'textColor' => '',
            'className' => '',
        ];
    }

    public function render(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);
        $content = $content ?: $attrs['content'];
        $level = max(1, min(6, (int)$attrs['level']));

        $htmlAttrs = [];
        
        if ($attrs['className']) {
            $htmlAttrs['class'] = $attrs['className'];
        }

        if ($attrs['align']) {
            $htmlAttrs['class'] = ($htmlAttrs['class'] ?? '') . ' text-' . $attrs['align'];
        }

        if ($attrs['textColor']) {
            $htmlAttrs['style'] = 'color: ' . $attrs['textColor'];
        }

        $attrString = $this->renderAttributes($htmlAttrs);

        return '<h' . $level . ($attrString ? ' ' . $attrString : '') . '>' . 
               $content . '</h' . $level . '>';
    }

    public function renderEditor(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);
        $content = $content ?: $attrs['content'];
        $level = $attrs['level'];

        return '<div class="block-editor heading-block">
            <div class="mb-2">
                <select class="form-select form-select-sm d-inline-block w-auto" data-block-attr="level">
                    <option value="1"' . ($level == 1 ? ' selected' : '') . '>H1</option>
                    <option value="2"' . ($level == 2 ? ' selected' : '') . '>H2</option>
                    <option value="3"' . ($level == 3 ? ' selected' : '') . '>H3</option>
                    <option value="4"' . ($level == 4 ? ' selected' : '') . '>H4</option>
                    <option value="5"' . ($level == 5 ? ' selected' : '') . '>H5</option>
                    <option value="6"' . ($level == 6 ? ' selected' : '') . '>H6</option>
                </select>
            </div>
            <input type="text" class="form-control" value="' . 
            $this->escape($content) . 
            '" data-block-content>
        </div>';
    }
}
