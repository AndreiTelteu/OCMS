<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlockItemTranslationValue extends Model
{
    /** @use HasFactory<\Database\Factories\PageBlockItemTranslationValueFactory> */
    use HasFactory;

    protected $fillable = [
        'block_item_id',
        'locale',
        'field_key',
        'value',
    ];

    public function blockItem(): BelongsTo
    {
        return $this->belongsTo(PageBlockItem::class, 'block_item_id');
    }
}
