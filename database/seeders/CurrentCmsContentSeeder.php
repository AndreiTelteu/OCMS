<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CurrentCmsContentSeeder extends Seeder
{
    private const SNAPSHOT_PATH = 'app/cms-content-snapshot.json';

    /**
     * Tables are ordered so every referenced parent row is inserted first.
     *
     * @var list<string>
     */
    private const TABLES = [
        'pages',
        'page_translations',
        'page_blocks',
        'page_block_translation_values',
        'page_block_items',
        'page_block_item_translation_values',
        'categories',
        'category_translations',
        'tags',
        'tag_translations',
        'articles',
        'article_translations',
        'article_category',
        'article_tag',
        'localized_routes',
    ];

    public function run(): void
    {
        $path = storage_path(self::SNAPSHOT_PATH);

        if (! is_file($path)) {
            throw new RuntimeException("CMS snapshot not found at [{$path}].");
        }

        $snapshot = json_decode(
            file_get_contents($path),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (($snapshot['version'] ?? null) !== 1 || ! isset($snapshot['tables'])) {
            throw new RuntimeException("Invalid CMS snapshot format at [{$path}].");
        }

        $missingTables = array_diff(self::TABLES, array_keys($snapshot['tables']));

        if ($missingTables !== []) {
            throw new RuntimeException('CMS snapshot is missing tables: '.implode(', ', $missingTables));
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($snapshot): void {
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::table($table)->delete();
                }

                foreach (self::TABLES as $table) {
                    $rows = $snapshot['tables'][$table];

                    if (! is_array($rows)) {
                        throw new RuntimeException("CMS snapshot table [{$table}] must contain an array.");
                    }

                    foreach (array_chunk($rows, 100) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
