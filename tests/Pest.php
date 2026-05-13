<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;
use Tests\TestCase;

$browserTestDatabase = dirname(__DIR__).'/database/testing.sqlite';

if (! file_exists($browserTestDatabase)) {
    touch($browserTestDatabase);
}

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

Playwright::setTimeout(10_000);
