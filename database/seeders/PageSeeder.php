<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $homePage = new Page([
            'status' => 'published',
            'template' => 'default',
            'is_home' => true,
            'published_at' => now(),
        ]);
        $homePage->save();
        $homePage->translateOrNew('en')->fill([
            'title' => 'Welcome',
            'slug' => 'home',
            'body' => 'Homepage content for the local CMS slice.',
            'seo_title' => 'Welcome',
            'seo_description' => 'Homepage content for the local CMS slice.',
        ]);
        $homePage->translateOrNew('ro')->fill([
            'title' => 'Bun venit',
            'slug' => 'acasa',
            'body' => 'Continutul paginii principale pentru slice-ul CMS local.',
            'seo_title' => 'Bun venit',
            'seo_description' => 'Continutul paginii principale pentru slice-ul CMS local.',
        ]);
        $homePage->save();

        $aboutPage = new Page([
            'status' => 'published',
            'template' => 'default',
            'is_home' => false,
            'published_at' => now(),
        ]);
        $aboutPage->save();
        $aboutPage->translateOrNew('en')->fill([
            'title' => 'About',
            'slug' => 'about',
            'body' => 'About page content.',
            'seo_title' => 'About',
            'seo_description' => 'About page content.',
        ]);
        $aboutPage->translateOrNew('ro')->fill([
            'title' => 'Despre',
            'slug' => 'despre',
            'body' => 'Continut pagina despre.',
            'seo_title' => 'Despre',
            'seo_description' => 'Continut pagina despre.',
        ]);
        $aboutPage->save();
    }
}
