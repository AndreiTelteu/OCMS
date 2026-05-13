<?php

namespace App\Support\Cms;

class BlockRegistry
{
    public static function definitions(): array
    {
        return [
            'hero' => [
                'fields' => ['title', 'subtitle', 'description', 'cta_label'],
                'settings' => ['image_id', 'alignment', 'overlay', 'cta_target_page_id'],
            ],
            'features' => [
                'fields' => ['title', 'description'],
                'settings' => ['icon', 'image_id', 'link_target_id'],
            ],
        ];
    }

    public static function block(string $type): array
    {
        return static::definitions()[$type] ?? ['fields' => [], 'settings' => []];
    }
}
