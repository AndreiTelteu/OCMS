<?php

use App\Exceptions\RootRouteCollisionException;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;

it('keeps article localized routes in sync when translations change or are deleted', function (): void {
    $article = createBrowserPublishedArticle([
        'en' => [
            'title' => 'News',
            'slug' => 'news',
            'excerpt' => 'Latest news.',
            'body' => 'Latest news body.',
        ],
        'ro' => [
            'title' => 'Stiri',
            'slug' => 'stiri',
            'excerpt' => 'Ultimele stiri.',
            'body' => 'Continut stiri.',
        ],
    ]);

    $article->translateOrNew('en')->fill([
        'title' => 'News',
        'slug' => 'updates',
        'excerpt' => 'Latest news.',
        'body' => 'Latest news body.',
    ])->save();

    createBrowserPublishedPage([
        'en' => [
            'title' => 'News archive',
            'slug' => 'news',
            'body' => 'Archive page content.',
        ],
    ]);

    visit('/updates')
        ->assertSee('News')
        ->assertSee('Latest news body.')
        ->assertSourceHas('"@type":"Article"')
        ->assertSourceHas('"inLanguage":"en"');

    visit('/news')
        ->assertSee('News archive')
        ->assertSee('Archive page content.');

    $article->translate('ro')?->delete();

    createBrowserPublishedPage([
        'en' => [
            'title' => 'Romanian placeholder',
            'slug' => 'romanian-placeholder',
            'body' => 'English body.',
        ],
        'ro' => [
            'title' => 'Pagina Stiri',
            'slug' => 'stiri',
            'body' => 'Pagina care a preluat vechiul slug.',
        ],
    ]);

    visit('/ro/stiri')
        ->assertSee('Pagina Stiri')
        ->assertSee('Pagina care a preluat vechiul slug.');
});

it('rejects root collisions between pages and articles instead of overwriting routes', function (): void {
    createBrowserPublishedPage([
        'en' => [
            'title' => 'About',
            'slug' => 'about',
            'body' => 'About page content.',
        ],
    ]);

    expect(fn (): Article => createBrowserPublishedArticle([
        'en' => [
            'title' => 'About article',
            'slug' => 'about',
            'excerpt' => 'About excerpt.',
            'body' => 'About article body.',
        ],
    ]))->toThrow(RootRouteCollisionException::class);

    visit('/about')
        ->assertSee('About')
        ->assertSee('About page content.')
        ->assertDontSee('About article body.');
});

it('recalculates descendant category paths after slug and parent changes', function (): void {
    $parent = createCategory([
        'status' => 'published',
    ], [
        'en' => [
            'name' => 'Parent',
            'slug' => 'parent',
            'description' => 'Parent description.',
        ],
    ]);

    $child = createCategory([
        'parent_id' => $parent->getKey(),
        'status' => 'published',
    ], [
        'en' => [
            'name' => 'Child',
            'slug' => 'child',
            'description' => 'Child description.',
        ],
    ]);

    $parent->translateOrNew('en')->fill([
        'name' => 'Topics',
        'slug' => 'topics',
        'description' => 'Parent description.',
        'path' => 'topics',
    ])->save();

    expect($child->fresh()->pathForLocale('en'))->toBe('topics/child');

    visit(route('without_locale.category.show', ['path' => 'topics/child'], false))
        ->assertSee('Child')
        ->assertSourceHas('"@type":"CollectionPage"')
        ->assertSourceHas('"inLanguage":"en"');

    $child->forceFill([
        'parent_id' => null,
    ])->save();

    expect($child->fresh()->pathForLocale('en'))->toBe('child');

    visit(route('without_locale.category.show', ['path' => 'child'], false))
        ->assertSee('Child');
});

it('only exposes published categories and tags publicly', function (): void {
    createCategory([
        'status' => 'draft',
    ], [
        'en' => [
            'name' => 'Draft category',
            'slug' => 'draft-category',
            'description' => 'Hidden category.',
        ],
    ]);

    createTag([
        'status' => 'draft',
    ], [
        'en' => [
            'name' => 'Draft tag',
            'slug' => 'draft-tag',
            'description' => 'Hidden tag.',
        ],
    ]);

    $this->get(route('without_locale.category.show', ['path' => 'draft-category']))
        ->assertNotFound();

    $this->get(route('without_locale.tag.show', ['slug' => 'draft-tag']))
        ->assertNotFound();
});

it('renders language metadata for category and tag pages', function (): void {
    createCategory([
        'status' => 'published',
    ], [
        'en' => [
            'name' => 'Guides',
            'slug' => 'guides',
            'description' => 'Guides description.',
        ],
        'ro' => [
            'name' => 'Ghiduri',
            'slug' => 'ghiduri',
            'description' => 'Descriere ghiduri.',
        ],
    ]);

    createTag([
        'status' => 'published',
    ], [
        'en' => [
            'name' => 'Laravel',
            'slug' => 'laravel',
            'description' => 'Laravel tag description.',
        ],
        'ro' => [
            'name' => 'Laravel RO',
            'slug' => 'laravel-ro',
            'description' => 'Descriere tag Laravel.',
        ],
    ]);

    visit(route('without_locale.category.show', ['path' => 'guides'], false))
        ->assertSee('Guides')
        ->assertSourceHas('"@type":"CollectionPage"')
        ->assertSourceHas('"inLanguage":"en"');

    visit(route('translated_ro.category.show', ['path' => 'ghiduri'], false))
        ->assertSee('Ghiduri')
        ->assertSourceHas('"inLanguage":"ro"');

    visit(route('without_locale.tag.show', ['slug' => 'laravel'], false))
        ->assertSee('Laravel')
        ->assertSourceHas('"@type":"CollectionPage"')
        ->assertSourceHas('"inLanguage":"en"');

    visit(route('translated_ro.tag.show', ['slug' => 'laravel-ro'], false))
        ->assertSee('Laravel RO')
        ->assertSourceHas('"inLanguage":"ro"');
});

/**
 * @param  array<string, array<string, string|null>>  $translations
 * @param  array<string, mixed>  $attributes
 */
function createBrowserPublishedPage(array $translations, array $attributes = []): Page
{
    $page = new Page(array_merge([
        'status' => 'published',
        'template' => 'default',
        'is_home' => false,
        'published_at' => now()->subMinute(),
    ], $attributes));

    $page->save();

    foreach ($translations as $locale => $translation) {
        $page->translateOrNew($locale)->fill($translation);
    }

    $page->save();

    return $page->fresh(['translations']);
}

/**
 * @param  array<string, array<string, string|null>>  $translations
 * @param  array<string, mixed>  $attributes
 */
function createBrowserPublishedArticle(array $translations, array $attributes = []): Article
{
    $article = new Article(array_merge([
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ], $attributes));

    $article->save();

    foreach ($translations as $locale => $translation) {
        $article->translateOrNew($locale)->fill($translation);
    }

    $article->save();

    return $article->fresh(['translations']);
}

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<string, array<string, string|null>>  $translations
 */
function createCategory(array $attributes, array $translations): Category
{
    $category = new Category(array_merge([
        'status' => 'draft',
        'sort_order' => 0,
    ], $attributes));

    $category->save();

    foreach ($translations as $locale => $translation) {
        $category->translateOrNew($locale)->fill([
            ...$translation,
            'path' => trim((string) $translation['slug'], '/'),
        ]);
    }

    $category->save();

    return $category->fresh(['translations', 'parent.translations']);
}

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<string, array<string, string|null>>  $translations
 */
function createTag(array $attributes, array $translations): Tag
{
    $tag = new Tag(array_merge([
        'status' => 'draft',
    ], $attributes));

    $tag->save();

    foreach ($translations as $locale => $translation) {
        $tag->translateOrNew($locale)->fill($translation);
    }

    $tag->save();

    return $tag->fresh(['translations']);
}
