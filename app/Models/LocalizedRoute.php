<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LocalizedRoute extends Model
{
    /** @use HasFactory<\Database\Factories\LocalizedRouteFactory> */
    use HasFactory;

    protected $fillable = [
        'locale',
        'path',
        'routable_type',
        'routable_id',
        'route_name',
    ];

    public function routable(): MorphTo
    {
        return $this->morphTo();
    }
}
