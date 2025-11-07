<?php

namespace FCMS\Blocks\Types;

use FCMS\Blocks\AbstractBlock;

/**
 * Listen-Block
 */
class ListBlock extends AbstractBlock
{
    public function getMetadata(): array
    {
        return [
            'name' => 'list',
            'title' => 'Liste',
            'icon' => 'list',
            'category' => 'text',
            'description' => 'Nummerierte oder Aufzählungsliste',
        ];
    }

    public function getDefaultAttributes(): array
    {
        return [
            'ordered' => false,
            'items' => [],
            'className' => '',
        ];
    }

    public function render(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);

        if (empty($attrs['items']) && empty($content)) {
            return '';
        }

        $tag = $attrs['ordered'] ? 'ol' : 'ul';
        $classAttr = $attrs['className'] ? ' class="' . $this->escape($attrs['className']) . '"' : '';

        $html = '<' . $tag . $classAttr . '>';

        if (!empty($attrs['items'])) {
            foreach ($attrs['items'] as $item) {
                $html .= '<li>' . $item . '</li>';
            }
        } elseif ($content) {
            $html .= $content;
        }

        $html .= '</' . $tag . '>';

        return $html;
    }

    public function renderEditor(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);

        $itemsText = '';
        if (!empty($attrs['items'])) {
            $itemsText = implode("\n", $attrs['items']);
        }

        return '<div class="block-editor list-block">
            <div class="mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="listOrdered"
                           data-block-attr="ordered"' . ($attrs['ordered'] ? ' checked' : '') . '>
                    <label class="form-check-label" for="listOrdered">
                        Nummerierte Liste
                    </label>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label">Listeneinträge (ein Eintrag pro Zeile)</label>
                <textarea class="form-control" rows="5" data-block-items>' .
                $this->escape($itemsText) .
                '</textarea>
            </div>
        </div>';
    }
}
