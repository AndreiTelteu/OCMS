<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagTranslation extends Model
{
    /** @use HasFactory<\Database\Factories\TagTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'locale',
        'name',
        'slug',
        'description',
        'seo_title',
        'seo_description',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
