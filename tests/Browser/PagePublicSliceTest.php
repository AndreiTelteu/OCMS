<?php

use App\Models\Page;

it('renders the root page slice with canonical, hreflang, schema, and switcher links', function (): void {
    createPublishedPage([
        'en' => [
            'title' => 'About',
            'slug' => 'about',
            'body' => 'About page content.',
            'seo_title' => 'About',
            'seo_description' => 'About page content.',
        ],
        'ro' => [
            'title' => 'Despre',
            'slug' => 'despre',
            'body' => 'Continut pagina despre.',
            'seo_title' => 'Despre',
            'seo_description' => 'Continut pagina despre.',
        ],
    ]);

    $page = visit('/about')
        ->assertTitle('About')
        ->assertSourceHas('<html lang="en">')
        ->assertSee('About')
        ->assertSee('About page content.')
        ->assertSourceHas('"@type":"WebPage"')
        ->assertAttributeContains('nav[aria-label="Language switcher"] a[hreflang="en"]', 'href', '/en/about')
        ->assertAttributeContains('nav[aria-label="Language switcher"] a[hreflang="ro"]', 'href', '/ro/despre');

    $origin = $page->script('window.location.origin');

    $page
        ->assertAttribute('link[rel="canonical"]', 'href', "{$origin}/about")
        ->assertAttribute('link[rel="alternate"][hreflang="ro"]', 'href', "{$origin}/ro/despre");
});

it('renders the translated secondary-locale root page route', function (): void {
    createPublishedPage([
        'en' => [
            'title' => 'About',
            'slug' => 'about',
            'body' => 'About page content.',
            'seo_title' => 'About',
            'seo_description' => 'About page content.',
        ],
        'ro' => [
            'title' => 'Despre',
            'slug' => 'despre',
            'body' => 'Continut pagina despre.',
            'seo_title' => 'Despre',
            'seo_description' => 'Continut pagina despre.',
        ],
    ]);

    $page = visit('/ro/despre')
        ->assertTitle('Despre')
        ->assertSourceHas('<html lang="ro">')
        ->assertSee('Despre')
        ->assertSee('Continut pagina despre.')
        ->assertAttributeContains('nav[aria-label="Language switcher"] a[hreflang="en"]', 'href', '/en/about')
        ->assertAttributeContains('nav[aria-label="Language switcher"] a[hreflang="ro"]', 'href', '/ro/despre');

    $origin = $page->script('window.location.origin');

    $page
        ->assertAttribute('link[rel="canonical"]', 'href', "{$origin}/ro/despre")
        ->assertAttribute('link[rel="alternate"][hreflang="en"]', 'href', "{$origin}/about");
});

it('releases the previous root slug after a page translation changes', function (): void {
    $page = createPublishedPage([
        'en' => [
            'title' => 'About',
            'slug' => 'about',
            'body' => 'About page content.',
        ],
    ]);

    $page->translateOrNew('en')->fill([
        'title' => 'About',
        'slug' => 'company',
        'body' => 'About page content.',
    ])->save();

    createPublishedPage([
        'en' => [
            'title' => 'Contact',
            'slug' => 'about',
            'body' => 'Contact page content.',
        ],
    ]);

    visit('/company')
        ->assertSee('About')
        ->assertSee('About page content.');

    visit('/about')
        ->assertSee('Contact')
        ->assertSee('Contact page content.')
        ->assertDontSee('About page content.');
});

it('renders the published home page slice on the localized home routes', function (): void {
    createPublishedPage([
        'en' => [
            'title' => 'Home',
            'slug' => '',
            'body' => 'Homepage in English.',
            'seo_title' => 'Home',
            'seo_description' => 'Homepage in English.',
        ],
        'ro' => [
            'title' => 'Acasa',
            'slug' => '',
            'body' => 'Pagina principala in romana.',
            'seo_title' => 'Acasa',
            'seo_description' => 'Pagina principala in romana.',
        ],
    ], [
        'is_home' => true,
    ]);

    $home = visit('/')
        ->assertTitle('Home')
        ->assertSourceHas('<html lang="en">')
        ->assertSee('Home')
        ->assertSee('Homepage in English.')
        ->assertAttributeContains('nav[aria-label="Language switcher"] a[hreflang="en"]', 'href', '/en')
        ->assertAttributeContains('nav[aria-label="Language switcher"] a[hreflang="ro"]', 'href', '/ro');

    $origin = $home->script('window.location.origin');

    $home
        ->assertAttribute('link[rel="canonical"]', 'href', $origin)
        ->assertAttribute('link[rel="alternate"][hreflang="ro"]', 'href', "{$origin}/ro")
        ->assertSourceHas('"@type":"WebPage"');

    visit('/ro')
        ->assertTitle('Acasa')
        ->assertSourceHas('<html lang="ro">')
        ->assertSee('Acasa')
        ->assertSee('Pagina principala in romana.');
});

/**
 * @param  array<string, array<string, string|null>>  $translations
 * @param  array<string, mixed>  $attributes
 */
function createPublishedPage(array $translations, array $attributes = []): Page
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
