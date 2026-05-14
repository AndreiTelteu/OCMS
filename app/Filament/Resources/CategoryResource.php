<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Services\Cms\CategoryPathSynchronizer;
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
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->required()
                            ->default('draft'),
                        Select::make('parent_id')
                            ->label('Parent category')
                            ->options(fn (?Category $record): array => static::parentOptions($record))
                            ->searchable()
                            ->preload(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),
                Tabs::make('Translations')
                    ->tabs(static::translationTabs())
                    ->columnSpanFull()
                    ->persistTabInQueryString('category-locale'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->state(fn (Category $record): string => $record->nameForLocale(config('cms.fallback_locale')) ?? 'Untitled')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('translations', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('parent_name')
                    ->label('Parent')
                    ->state(fn (Category $record): ?string => $record->parent?->nameForLocale(config('cms.fallback_locale')))
                    ->placeholder('Root')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['translations', 'parent.translations']);
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof Category) {
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
                'parent_id',
                'sort_order',
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

    public static function fillFormData(Category $record): array
    {
        return [
            'status' => $record->status,
            'parent_id' => $record->parent_id,
            'sort_order' => $record->sort_order,
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

    public static function persistTranslations(Category $category, array $translations): void
    {
        foreach ($translations as $locale => $translation) {
            $normalizedTranslation = array_map(
                static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
                $translation,
            );

            $hasContent = collect($normalizedTranslation)
                ->contains(fn (mixed $value): bool => filled($value));

            /** @var CategoryTranslation|null $existingTranslation */
            $existingTranslation = $category->translate($locale, false);

            if (! $hasContent) {
                $existingTranslation?->delete();

                continue;
            }

            $category->translateOrNew($locale)->fill($normalizedTranslation);
            $category->translateOrNew($locale)->path = app(CategoryPathSynchronizer::class)->pathFor($category, $locale) ?? '';
        }

        $category->save();
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
                ->maxLength(255),
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

    /**
     * @return array<int, string>
     */
    protected static function parentOptions(?Category $record = null): array
    {
        return Category::query()
            ->with('translations')
            ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->getKey() => $category->nameForLocale(config('cms.fallback_locale')) ?? "Category #{$category->getKey()}",
            ])
            ->all();
    }
}
