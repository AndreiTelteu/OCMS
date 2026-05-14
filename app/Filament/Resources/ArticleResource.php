<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages\CreateArticle;
use App\Filament\Resources\ArticleResource\Pages\EditArticle;
use App\Filament\Resources\ArticleResource\Pages\ListArticles;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Rules\ValidRootContentSlug;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
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

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Article')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->required()
                            ->default('draft'),
                        Select::make('author_id')
                            ->label('Author')
                            ->options(static::authorOptions())
                            ->searchable()
                            ->preload(),
                        TextInput::make('featured_image_path')
                            ->label('Featured image path')
                            ->maxLength(255),
                        DateTimePicker::make('published_at'),
                        Select::make('category_ids')
                            ->label('Categories')
                            ->multiple()
                            ->options(static::categoryOptions())
                            ->searchable()
                            ->preload(),
                        Select::make('tag_ids')
                            ->label('Tags')
                            ->multiple()
                            ->options(static::tagOptions())
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                Tabs::make('Translations')
                    ->tabs(static::translationTabs())
                    ->columnSpanFull()
                    ->persistTabInQueryString('article-locale'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->state(fn (Article $record): string => $record->titleForLocale(config('cms.fallback_locale')) ?? 'Untitled')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('translations', function (Builder $query) use ($search): void {
                            $query->where('title', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->placeholder('Unknown')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge(),
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
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['translations', 'author', 'categories', 'tags']);
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof Article) {
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
                'author_id',
                'featured_image_path',
                'published_at',
            ]),
            'category_ids' => array_values((array) ($data['category_ids'] ?? [])),
            'tag_ids' => array_values((array) ($data['tag_ids'] ?? [])),
            'translations' => collect(config('cms.supported_locales'))
                ->mapWithKeys(function (string $locale) use ($data): array {
                    $translation = Arr::only((array) data_get($data, "translations.{$locale}", []), [
                        'title',
                        'slug',
                        'excerpt',
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

    public static function fillFormData(Article $record): array
    {
        return [
            'status' => $record->status,
            'author_id' => $record->author_id,
            'featured_image_path' => $record->featured_image_path,
            'published_at' => $record->published_at,
            'category_ids' => $record->categories()->pluck('categories.id')->all(),
            'tag_ids' => $record->tags()->pluck('tags.id')->all(),
            'translations' => collect(config('cms.supported_locales'))
                ->mapWithKeys(function (string $locale) use ($record): array {
                    $translation = $record->translate($locale, false);

                    return [$locale => [
                        'title' => $translation?->title,
                        'slug' => $translation?->slug,
                        'excerpt' => $translation?->excerpt,
                        'body' => $translation?->body,
                        'seo_title' => $translation?->seo_title,
                        'seo_description' => $translation?->seo_description,
                    ]];
                })
                ->all(),
        ];
    }

    public static function persistTranslations(Article $article, array $translations): void
    {
        foreach ($translations as $locale => $translation) {
            $normalizedTranslation = array_map(
                static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
                $translation,
            );

            $hasContent = collect($normalizedTranslation)
                ->contains(fn (mixed $value): bool => filled($value));

            /** @var ArticleTranslation|null $existingTranslation */
            $existingTranslation = $article->translate($locale, false);

            if (! $hasContent) {
                $existingTranslation?->delete();

                continue;
            }

            $article->translateOrNew($locale)->fill($normalizedTranslation);
        }

        $article->save();
    }

    public static function syncRelationships(Article $article, array $categoryIds, array $tagIds): void
    {
        $article->categories()->sync($categoryIds);
        $article->tags()->sync($tagIds);
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
                    fn (?Article $record) => Rule::unique('article_translations', 'slug')
                        ->where(fn ($query) => $query->where('locale', $locale))
                        ->ignore($record?->translate($locale, false)?->getKey()),
                    fn (?Article $record): ValidRootContentSlug => new ValidRootContentSlug($locale, Article::class, $record?->getKey()),
                ]),
            Textarea::make("translations.{$locale}.excerpt")
                ->label('Excerpt')
                ->rows(3)
                ->columnSpanFull(),
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

    /**
     * @return array<int, string>
     */
    protected static function authorOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function categoryOptions(): array
    {
        return Category::query()
            ->with('translations')
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->getKey() => $category->nameForLocale(config('cms.fallback_locale')) ?? "Category #{$category->getKey()}",
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function tagOptions(): array
    {
        return Tag::query()
            ->with('translations')
            ->get()
            ->mapWithKeys(fn (Tag $tag): array => [
                $tag->getKey() => $tag->nameForLocale(config('cms.fallback_locale')) ?? "Tag #{$tag->getKey()}",
            ])
            ->all();
    }
}
