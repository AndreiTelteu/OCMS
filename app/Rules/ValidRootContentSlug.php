<?php

namespace App\Rules;

use App\Models\LocalizedRoute;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ValidRootContentSlug implements ValidationRule
{
    public function __construct(
        private readonly string $locale,
        private readonly string $routableType,
        private readonly ?int $routableId = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug = trim((string) $value, '/');

        if ($slug === '') {
            return;
        }

        $reservedSlugs = collect(config('cms.reserved_root_slugs', []))
            ->map(fn (string $reservedSlug): string => Str::lower(trim($reservedSlug, '/')))
            ->all();

        if (in_array(Str::lower($slug), $reservedSlugs, true)) {
            $fail('This slug is reserved for a system route.');

            return;
        }

        $conflictExists = LocalizedRoute::query()
            ->where('locale', $this->locale)
            ->where('path', $slug)
            ->when(
                $this->routableId !== null,
                fn ($query) => $query->where(function ($query): void {
                    $query
                        ->where('routable_type', '!=', $this->routableType)
                        ->orWhere('routable_id', '!=', $this->routableId);
                }),
            )
            ->exists();

        if ($conflictExists) {
            $fail('This slug is already used by another public route in this locale.');
        }
    }
}
