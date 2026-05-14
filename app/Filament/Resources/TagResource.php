<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages\CreateTag;
use App\Filament\Resources\TagResource\Pages\EditTag;
use App\Filament\Resources\TagResource\Pages\ListTags;
use App\Models\Tag;
use App\Models\TagTranslation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use UnitEnum;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tag')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->required()
                            ->default('draft'),
                    ])
                    ->columns(1),
                Tabs::make('Translations')
                    ->tabs(static::translationTabs())
                    ->columnSpanFull()
                    ->persistTabInQueryString('tag-locale'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->state(fn (Tag $record): string => $record->nameForLocale(config('cms.fallback_locale')) ?? 'Untitled')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('translations', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('translations');
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof Tag) {
            return parent::getRecordTitle($record);
        }

        return $record->nameForLocale(config('cms.fallback_locale')) ?? parent::getRecordTitle($record);
    }

    /**
     * @return array<string, mixed>
     */
    public static function mutateFormData(array $data): array
    {
        return [
            'attributes' => Arr::only($data, [
                'status',
            ]),
            'translations' => collect(config('cms.supported_locales'))
                ->mapWithKeys(function (string $locale) use ($data): array {
                    $translation = Arr::only((array) data_get($data, "translations.{$locale}", []), [
                        'name',
                        'slug',
                        'description',
                        'seo_title',
                        'seo_description',
                    ]);

                    $translation['slug'] = filled($translation['slug'] ?? null)
                        ? trim((string) $translation['slug'], '/')
                        : null;

                    return [$locale => $translation];
                })
                ->all(),
        ];
    }

    public static function fillFormData(Tag $record): array
    {
        return [
            'status' => $record->status,
            'translations' => collect(config('cms.supported_locales'))
                ->mapWithKeys(function (string $locale) use ($record): array {
                    $translation = $record->translate($locale, false);

                    return [$locale => [
                        'name' => $translation?->name,
                        'slug' => $translation?->slug,
                        'description' => $translation?->description,
                        'seo_title' => $translation?->seo_title,
                        'seo_description' => $translation?->seo_description,
                    ]];
                })
                ->all(),
        ];
    }

    public static function persistTranslations(Tag $tag, array $translations): void
    {
        foreach ($translations as $locale => $translation) {
            $normalizedTranslation = array_map(
                static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
                $translation,
            );

            $hasContent = collect($normalizedTranslation)
                ->contains(fn (mixed $value): bool => filled($value));

            /** @var TagTranslation|null $existingTranslation */
            $existingTranslation = $tag->translate($locale, false);

            if (! $hasContent) {
                $existingTranslation?->delete();

                continue;
            }

            $tag->translateOrNew($locale)->fill($normalizedTranslation);
        }

        $tag->save();
    }

    /**
     * @return array<Tab>
     */
    protected static function translationTabs(): array
    {
        $fallbackLocale = config('cms.fallback_locale');

        return collect(config('cms.supported_locales'))
            ->map(fn (string $locale): Tab => Tab::make(strtoupper($locale))
                ->schema(static::translationSchema($locale, $fallbackLocale))
                ->columns(2))
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    protected static function translationSchema(string $locale, string $fallbackLocale): array
    {
        return [
            TextInput::make("translations.{$locale}.name")
                ->label('Name')
                ->required($locale === $fallbackLocale)
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($locale): void {
                    if (filled($get("translations.{$locale}.slug"))) {
                        return;
                    }

                    $set("translations.{$locale}.slug", Str::slug((string) $state));
                })
                ->maxLength(255),
            TextInput::make("translations.{$locale}.slug")
                ->label('Slug')
                ->required($locale === $fallbackLocale)
                ->alphaDash()
                ->maxLength(255)
                ->rules([
                    fn (?Tag $record) => Rule::unique('tag_translations', 'slug')
                        ->where(fn ($query) => $query->where('locale', $locale))
                        ->ignore($record?->translate($locale, false)?->getKey()),
                ]),
            Textarea::make("translations.{$locale}.description")
                ->label('Description')
                ->rows(4)
                ->columnSpanFull(),
            TextInput::make("translations.{$locale}.seo_title")
                ->label('SEO title')
                ->maxLength(255),
            Textarea::make("translations.{$locale}.seo_description")
                ->label('SEO description')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }
}
