<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlockTranslationValue extends Model
{
    /** @use HasFactory<\Database\Factories\PageBlockTranslationValueFactory> */
    use HasFactory;

    protected $fillable = [
        'block_id',
        'locale',
        'field_key',
        'value',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(PageBlock::class, 'block_id');
    }
}
