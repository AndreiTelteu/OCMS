<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithBlockTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageBlockItem extends Model
{
    /** @use HasFactory<\Database\Factories\PageBlockItemFactory> */
    use HasFactory;
    use InteractsWithBlockTranslations;

    protected $fillable = [
        'block_id',
        'type',
        'sort_order',
        'settings_json',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(PageBlock::class, 'block_id');
    }

    public function translationValues(): HasMany
    {
        return $this->hasMany(PageBlockItemTranslationValue::class, 'block_item_id');
    }
}
