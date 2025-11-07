<?php

namespace FCMS\Blocks\Types;

use FCMS\Blocks\AbstractBlock;

/**
 * Zitat-Block
 */
class QuoteBlock extends AbstractBlock
{
    public function getMetadata(): array
    {
        return [
            'name' => 'quote',
            'title' => 'Zitat',
            'icon' => 'quote',
            'category' => 'text',
            'description' => 'Hervorgehobenes Zitat',
        ];
    }

    public function getDefaultAttributes(): array
    {
        return [
            'content' => '',
            'citation' => '',
            'align' => '',
            'className' => '',
        ];
    }

    public function render(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);
        $content = $content ?: $attrs['content'];

        if (empty($content)) {
            return '';
        }

        $classes = ['wp-block-quote'];
        
        if ($attrs['align']) {
            $classes[] = 'text-' . $attrs['align'];
        }
        
        if ($attrs['className']) {
            $classes[] = $attrs['className'];
        }

        $html = '<blockquote class="' . implode(' ', $classes) . '">';
        $html .= '<p>' . $content . '</p>';
        
        if ($attrs['citation']) {
            $html .= '<cite>' . $this->escape($attrs['citation']) . '</cite>';
        }
        
        $html .= '</blockquote>';

        return $html;
    }

    public function renderEditor(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);
        $content = $content ?: $attrs['content'];

        return '<div class="block-editor quote-block">
            <div class="mb-2">
                <label class="form-label">Zitat</label>
                <textarea class="form-control" rows="3" data-block-content>' . 
                $this->escape($content) . 
                '</textarea>
            </div>
            <div class="mb-2">
                <label class="form-label">Quelle (optional)</label>
                <input type="text" class="form-control" value="' . 
                $this->escape($attrs['citation']) . 
                '" data-block-attr="citation" placeholder="z.B. Albert Einstein">
            </div>
        </div>';
    }
}
