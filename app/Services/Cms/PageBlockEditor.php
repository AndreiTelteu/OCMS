<?php

namespace App\Services\Cms;

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageBlockItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PageBlockEditor
{
    public function sync(Page $page, array $blocks): void
    {
        DB::transaction(function () use ($page, $blocks): void {
            $existingIds = [];

            foreach (array_values($blocks) as $index => $blockData) {
                $block = $page->blocks()->updateOrCreate(
                    ['id' => $blockData['id'] ?? null],
                    [
                        'type' => $blockData['type'],
                        'sort_order' => $index,
                        'settings_json' => Arr::get($blockData, 'settings_json', []),
                        'is_active' => Arr::get($blockData, 'is_active', true),
                    ]
                );

                $existingIds[] = $block->id;
                $this->syncTranslations($block, Arr::get($blockData, 'translations', []));
                $this->syncItems($block, Arr::get($blockData, 'items', []));
            }

            $page->blocks()->whereNotIn('id', $existingIds)->delete();
        });
    }

    private function syncTranslations(PageBlock $block, array $translations): void
    {
        $block->translationValues()->delete();

        foreach ($translations as $locale => $fields) {
            foreach ((array) $fields as $fieldKey => $value) {
                $block->translationValues()->create([
                    'locale' => $locale,
                    'field_key' => $fieldKey,
                    'value' => $value,
                ]);
            }
        }
    }

    private function syncItems(PageBlock $block, array $items): void
    {
        $existingIds = [];

        foreach (array_values($items) as $index => $itemData) {
            $item = $block->items()->updateOrCreate(
                ['id' => $itemData['id'] ?? null],
                [
                    'type' => $itemData['type'],
                    'sort_order' => $index,
                    'settings_json' => Arr::get($itemData, 'settings_json', []),
                ]
            );

            $existingIds[] = $item->id;
            $this->syncItemTranslations($item, Arr::get($itemData, 'translations', []));
        }

        $block->items()->whereNotIn('id', $existingIds)->delete();
    }

    private function syncItemTranslations(PageBlockItem $item, array $translations): void
    {
        $item->translationValues()->delete();

        foreach ($translations as $locale => $fields) {
            foreach ((array) $fields as $fieldKey => $value) {
                $item->translationValues()->create([
                    'locale' => $locale,
                    'field_key' => $fieldKey,
                    'value' => $value,
                ]);
            }
        }
    }
}
