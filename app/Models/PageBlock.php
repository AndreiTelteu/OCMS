<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithBlockTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageBlock extends Model
{
    /** @use HasFactory<\Database\Factories\PageBlockFactory> */
    use HasFactory;
    use InteractsWithBlockTranslations;

    protected $fillable = [
        'page_id',
        'type',
        'sort_order',
        'settings_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
            'is_active' => 'bool',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PageBlockItem::class, 'block_id')->orderBy('sort_order');
    }

    public function translationValues(): HasMany
    {
        return $this->hasMany(PageBlockTranslationValue::class, 'block_id');
    }
}
