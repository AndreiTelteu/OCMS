<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageResource\Pages\ListPages;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Rules\ValidRootContentSlug;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Page')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->required()
                            ->default('draft'),
                        TextInput::make('template')
                            ->maxLength(255),
                        Toggle::make('is_home')
                            ->inline(false)
                            ->default(false),
                        DateTimePicker::make('published_at'),
                    ])
                    ->columns(2),
                Tabs::make('Translations')
                    ->tabs(static::translationTabs())
                    ->columnSpanFull()
                    ->persistTabInQueryString('page-locale'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->state(fn (Page $record): string => $record->titleForLocale(config('cms.fallback_locale')) ?? 'Untitled')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('translations', function (Builder $query) use ($search): void {
                            $query->where('title', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('status')
                    ->badge(),
                IconColumn::make('is_home')
                    ->label('Home')
                    ->boolean(),
                TextColumn::make('template')
                    ->placeholder('Default')
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('translations');
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof Page) {
            return parent::getRecordTitle($record);
        }

        return $record->titleForLocale(config('cms.fallback_locale')) ?? parent::getRecordTitle($record);
    }

    /**
     * @return array<string, mixed>
     */
    public static function mutateFormData(array $data): array
    {
        return [
            'attributes' => Arr::only($data, [
                'status',
                'template',
                'is_home',
                'published_at',
            ]),
            'translations' => collect(config('cms.supported_locales'))
                ->mapWithKeys(function (string $locale) use ($data): array {
                    $translation = Arr::only((array) data_get($data, "translations.{$locale}", []), [
                        'title',
                        'slug',
                        'body',
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

    public static function fillFormData(Page $record): array
    {
        return [
            'status' => $record->status,
            'template' => $record->template,
            'is_home' => $record->is_home,
            'published_at' => $record->published_at,
            'translations' => collect(config('cms.supported_locales'))
                ->mapWithKeys(function (string $locale) use ($record): array {
                    $translation = $record->translate($locale, false);

                    return [$locale => [
                        'title' => $translation?->title,
                        'slug' => $translation?->slug,
                        'body' => $translation?->body,
                        'seo_title' => $translation?->seo_title,
                        'seo_description' => $translation?->seo_description,
                    ]];
                })
                ->all(),
        ];
    }

    public static function persistTranslations(Page $page, array $translations): void
    {
        foreach ($translations as $locale => $translation) {
            $normalizedTranslation = array_map(
                static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
                $translation,
            );

            $hasContent = collect($normalizedTranslation)
                ->contains(fn (mixed $value): bool => filled($value));

            /** @var PageTranslation|null $existingTranslation */
            $existingTranslation = $page->translate($locale, false);

            if (! $hasContent) {
                $existingTranslation?->delete();

                continue;
            }

            $page->translateOrNew($locale)->fill($normalizedTranslation);
        }

        $page->save();
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
            TextInput::make("translations.{$locale}.title")
                ->label('Title')
                ->required(fn (Get $get): bool => $locale === $fallbackLocale && ! (bool) $get('is_home'))
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($locale): void {
                    if ($get('is_home')) {
                        return;
                    }

                    if (filled($get("translations.{$locale}.slug"))) {
                        return;
                    }

                    $set("translations.{$locale}.slug", Str::slug((string) $state));
                })
                ->maxLength(255),
            TextInput::make("translations.{$locale}.slug")
                ->label('Slug')
                ->required(fn (Get $get): bool => $locale === $fallbackLocale && ! (bool) $get('is_home'))
                ->alphaDash()
                ->maxLength(255)
                ->rules([
                    fn (?Page $record) => Rule::unique('page_translations', 'slug')
                        ->where(fn ($query) => $query->where('locale', $locale))
                        ->ignore($record?->translate($locale, false)?->getKey()),
                    fn (?Page $record): ValidRootContentSlug => new ValidRootContentSlug($locale, Page::class, $record?->getKey()),
                ]),
            MarkdownEditor::make("translations.{$locale}.body")
                ->label('Body')
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
