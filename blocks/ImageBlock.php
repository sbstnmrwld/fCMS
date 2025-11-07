<?php

namespace FCMS\Blocks;

use FCMS\Blocks\AbstractBlock;

/**
 * Bild-Block
 */
class ImageBlock extends AbstractBlock
{
    public function getMetadata(): array
    {
        return [
            'name' => 'image',
            'title' => 'Bild',
            'icon' => 'image',
            'category' => 'media',
            'description' => 'Einzelnes Bild mit optionaler Beschriftung',
        ];
    }

    public function getDefaultAttributes(): array
    {
        return [
            'url' => '',
            'alt' => '',
            'caption' => '',
            'width' => '',
            'height' => '',
            'align' => '',
            'className' => '',
        ];
    }

    public function render(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);

        if (empty($attrs['url'])) {
            return '';
        }

        $figureClass = 'wp-block-image';
        if ($attrs['align']) {
            $figureClass .= ' align' . $attrs['align'];
        }
        if ($attrs['className']) {
            $figureClass .= ' ' . $attrs['className'];
        }

        $imgAttrs = [
            'src' => $attrs['url'],
            'alt' => $attrs['alt'],
            'class' => 'img-fluid',
        ];

        if ($attrs['width']) {
            $imgAttrs['width'] = $attrs['width'];
        }
        if ($attrs['height']) {
            $imgAttrs['height'] = $attrs['height'];
        }

        $html = '<figure class="' . $this->escape($figureClass) . '">';
        $html .= '<img ' . $this->renderAttributes($imgAttrs) . '>';

        if ($attrs['caption']) {
            $html .= '<figcaption class="wp-element-caption">' .
                     $this->escape($attrs['caption']) . '</figcaption>';
        }

        $html .= '</figure>';

        return $html;
    }

    public function renderEditor(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);

        return '<div class="block-editor image-block">
            <div class="mb-2">
                <label class="form-label">Bild-URL</label>
                <div class="input-group">
                    <input type="url" class="form-control" value="' .
                    $this->escape($attrs['url']) .
                    '" data-block-attr="url" placeholder="https://...">
                    <button class="btn btn-outline-secondary" type="button">Durchsuchen</button>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label">Alt-Text</label>
                <input type="text" class="form-control" value="' .
                $this->escape($attrs['alt']) .
                '" data-block-attr="alt">
            </div>
            <div class="mb-2">
                <label class="form-label">Bildunterschrift</label>
                <input type="text" class="form-control" value="' .
                $this->escape($attrs['caption']) .
                '" data-block-attr="caption">
            </div>
            ' . ($attrs['url'] ? '<img src="' . $this->escape($attrs['url']) . '" class="img-fluid mt-2" alt="">' : '') . '
        </div>';
    }
}
