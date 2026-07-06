# Repository Instructions

## Project Shape
- Laravel 13.9 app on PHP `^8.3`; Filament 5 admin lives at `/admin` via `app/Providers/Filament/AdminPanelProvider.php`.
- This repo currently has no README. The original direction is in `cms-filament-multilanguage-plan.md`, but code and Git history are newer; trust executable code first.
- Public CMS content is `Page`, `Article`, `Category`, and `Tag`, translated with Astrotomic separate translation tables, not JSON columns on the main models.
- Supported locales are hard-coded in `config/cms.php` as `en` and `ro`; `APP_MAIN_LANG_PREFIX` controls whether the fallback locale is hidden in canonical URLs through `config/localizer.php`.

## Commands
- First setup: `composer install`, copy `.env.example` to `.env`, `php artisan key:generate`, `php artisan migrate`, `npm install`, then `npm run build`.
- Repo setup script: `composer setup` runs install, env/key, migrations, npm install with `--ignore-scripts`, and Vite build.
- Dev stack: `composer dev` starts `php artisan serve`, queue listener, Pail logs, and Vite concurrently.
- Browser test prerequisites: `npm install` and `npm run playwright:install` before first local browser test run.
- Main test command: `composer test`, which clears config and runs `composer test:browser`.
- Focused browser tests: `./vendor/bin/pest tests/Browser/PagePublicSliceTest.php` or add `--filter 'test name'`.
- CI currently runs `php artisan test` on PHP 8.3, 8.4, and 8.5; `phpunit.xml` only includes `tests/Browser`.
- Useful hardening checks for routing/localization work: `php artisan route:list` and `php artisan route:cache`.
- PHP formatting command is `./vendor/bin/pint`; StyleCI uses Laravel preset with `no_unused_imports` disabled.

## Docker Helper
- `./dc` wraps `docker compose`, exports host `PUID`/`PGID`, and uses the `web` service mounted at `/app`; the app is served from the container on host port `8445`.
- Container control: `./dc` shows `ps -a`, `./dc up` starts detached services, `./dc down` stops/removes them, `./dc restart [service]`, `./dc recreate [service]`, `./dc build [service]`, and `./dc tail [service]` follows the last 100 log lines.
- Run shell commands in the app container with `./dc web <command>` or open a shell with `./dc web`.
- Run Artisan in Docker as `./dc web-a <artisan args>`; normal migration workflow is `./dc up` then `./dc web-a migrate`.
- Destructive reset workflow is `./dc web-a migrate:fresh --force`; only use it when wiping the Docker DB is intended.
- Run Composer in Docker as `./dc web-c <composer args>`, for example `./dc web-c install` or `./dc web-c test`.

## Routing And URLs
- Public route order in `routes/web.php` is intentional: localized home/sitemap, translated category/tag routes, then final localized catch-all `/{path?}` for root content.
- Middleware order in `bootstrap/app.php` is intentional: `ResolveCmsLocale`, package `SetLocale`, package `RedirectLocale`, `CanonicalizeCmsLocale`, `SubstituteBindings`, then `ShareSeoContext`.
- Do not hand-build locale prefixes or CMS paths. Use `App\Services\Cms\LocalizedUrlGenerator` for pages, articles, categories, tags, alternates, and switcher links.
- Localizer route names include `without_locale.*`, `with_locale.*`, and `translated_{locale}.*`; translated URI labels are in `lang/en/routes.php` and `lang/ro/routes.php` using full URI keys like `category/{path}`.
- Pages and articles both publish at the root through `RootContentController` plus `RootContentResolver`; `PageController` and `ArticleController` exist but are not wired in `routes/web.php`.
- Root page/article collisions are prevented by `localized_routes` with unique `(locale, path)` and `LocalizedRouteRegistry`; published page/article observers keep this registry in sync.
- `config/cms.php` reserves root slugs `admin`, `livewire`, and `up`; update this list when adding new manual root routes before the catch-all.

## CMS Persistence
- Translation fallback is centralized in `HasTranslationFallbacks`: current locale translation falls back to `config('cms.fallback_locale')`.
- Page/article `localized_routes` are registered only for published records; bulk DB updates can bypass observers and leave public routes stale.
- Category nesting paths are materialized in `category_translations.path` by `CategoryPathSynchronizer` observers and recalculated synchronously.
- Filament resources manually split attributes and translations in `mutateFormData`, `fillFormData`, and `persistTranslations`; do not assume Filament's default model save persists translated fields.
- Fallback-locale title/slug fields are required in Filament for articles, categories, and tags; pages skip slug/title requirement when `is_home` is true.
- The block system is scaffolding: relational block tables/models, `PageBlockEditor`, and `BlockRegistry` exist, but current public views ignore blocks and no Filament block builder is wired.

## SEO And Sitemap
- SEO and schema output is centralized in `SeoData`, `SchemaBuilder`, `ShareSeoContext`, and `resources/views/cms/*.blade.php`.
- Canonical/alternate/switcher URLs for entity pages should come from `LocalizedUrlGenerator`; current-route helpers are only used for the active request context.
- `SitemapBuilder` emits pages, articles, categories, and tags, using fallback-locale canonical URLs plus locale alternates.

## Tests
- Tests are Pest Browser tests in `tests/Browser`; there are no active Unit/Feature suites.
- `tests/Pest.php` creates `database/testing.sqlite`, applies `RefreshDatabase`, and sets Playwright timeout to 10 seconds.
- Existing browser tests cover localized root pages, home routes, page/article slug registry sync, category path sync, published-only category/tag access, SEO metadata, and sitemap alternates.
