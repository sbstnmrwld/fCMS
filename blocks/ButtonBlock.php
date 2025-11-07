<?php

namespace FCMS\Blocks\Types;

use FCMS\Blocks\AbstractBlock;

/**
 * Button-Block
 */
class ButtonBlock extends AbstractBlock
{
    public function getMetadata(): array
    {
        return [
            'name' => 'button',
            'title' => 'Button',
            'icon' => 'button',
            'category' => 'design',
            'description' => 'Button mit Link',
        ];
    }

    public function getDefaultAttributes(): array
    {
        return [
            'text' => 'Button',
            'url' => '',
            'style' => 'primary',
            'size' => '',
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

        $classes = ['btn', 'btn-' . $attrs['style']];
        
        if ($attrs['size']) {
            $classes[] = 'btn-' . $attrs['size'];
        }
        
        if ($attrs['className']) {
            $classes[] = $attrs['className'];
        }

        $wrapperClass = '';
        if ($attrs['align']) {
            $wrapperClass = ' text-' . $attrs['align'];
        }

        $html = '<div class="wp-block-button' . $wrapperClass . '">';
        $html .= '<a href="' . $this->escape($attrs['url']) . '" class="' . implode(' ', $classes) . '">';
        $html .= $this->escape($attrs['text']);
        $html .= '</a>';
        $html .= '</div>';

        return $html;
    }

    public function renderEditor(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);

        return '<div class="block-editor button-block">
            <div class="mb-2">
                <label class="form-label">Button-Text</label>
                <input type="text" class="form-control" value="' . 
                $this->escape($attrs['text']) . 
                '" data-block-attr="text">
            </div>
            <div class="mb-2">
                <label class="form-label">Link-URL</label>
                <input type="url" class="form-control" value="' . 
                $this->escape($attrs['url']) . 
                '" data-block-attr="url">
            </div>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Stil</label>
                    <select class="form-select" data-block-attr="style">
                        <option value="primary"' . ($attrs['style'] == 'primary' ? ' selected' : '') . '>Primary</option>
                        <option value="secondary"' . ($attrs['style'] == 'secondary' ? ' selected' : '') . '>Secondary</option>
                        <option value="success"' . ($attrs['style'] == 'success' ? ' selected' : '') . '>Success</option>
                        <option value="danger"' . ($attrs['style'] == 'danger' ? ' selected' : '') . '>Danger</option>
                        <option value="outline-primary"' . ($attrs['style'] == 'outline-primary' ? ' selected' : '') . '>Outline</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Größe</label>
                    <select class="form-select" data-block-attr="size">
                        <option value=""' . (!$attrs['size'] ? ' selected' : '') . '>Standard</option>
                        <option value="sm"' . ($attrs['size'] == 'sm' ? ' selected' : '') . '>Klein</option>
                        <option value="lg"' . ($attrs['size'] == 'lg' ? ' selected' : '') . '>Groß</option>
                    </select>
                </div>
            </div>
        </div>';
    }
}
