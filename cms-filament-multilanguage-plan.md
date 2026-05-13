# Plan tehnic actualizat: CMS Laravel + Filament Admin Panel, multilanguage URL-first

## 1. Obiectiv

Implementarea unui CMS Laravel cu Filament Admin Panel, complet multilanguage și SEO-friendly, în care:

- localizarea URL-urilor este bazată pe prefix de limbă, cu opțiune clară dacă limba principală are prefix sau nu;
- toate linkurile generate în aplicație folosesc URL-ul localizat corect;
- paginile statice și conținutul editorial folosesc traduceri în tabele separate;
- articolele sunt publicate la `/{locale}/{slug}` sau `/{slug}`, fără `/blog`;
- categoriile suportă nesting nelimitat;
- sitemap-ul, canonical-urile, hreflang-urile și schema.org sunt generate corect per limbă;
- schimbarea limbii păstrează utilizatorul pe aceeași pagină logică.

---

## 2. Presupuneri

- Proiectul folosește Laravel 13+ și Filament 4.
- Localele suportate sunt finite și cunoscute din configurare, nu sunt create dinamic per request.
- Limba implicită este controlată din `config/app.php` + `config/localizer.php`.
- Conținutul public principal este: pagini statice, articole, categorii, tag-uri.
- Frontend-ul folosește exclusiv named routes și servicii de URL generation, nu concatenare manuală de path-uri.
- Pentru block builder vrem experiență de editor tip builder în Filament, dar persistență relațională, nu JSON blob tradus per limbă.

---

## 3. Comparație scurtă de opțiuni relevante

| Zonă | Opțiune | Avantaje | Dezavantaje | Decizie |
|---|---|---|---|---|
| Localizare rute | `mcamara/laravel-localization` | ecosistem cunoscut | rutare dinamică, compromisuri la cache și mentenanță | **Respins** |
| Localizare rute | `niels-numbers/laravel-localizer` | rute statice, compatibil nativ cu `php artisan route:cache`, helpers clare pentru canonical/switcher | cere disciplină pe named routes și ordine corectă de middleware | **Ales** |
| Traduceri modele | coloane JSON pe modelul principal | implementare rapidă | indexare slabă, unicitate dificilă pentru slug, modelare slabă pentru SEO și binding | **Respins** |
| Traduceri modele | `astrotomic/laravel-translatable` + tabele separate | modelare curată, unicitate per limbă, compatibil bun cu Eloquent și admin | mai multe tabele și query-uri | **Ales** |
| Block builder | JSON blob complet per limbă | simplu de serializat | duplică imagini/structură, greu de validat, greu de sincronizat | **Respins** |
| Block builder | builder relațional: structură neutră + câmpuri traductibile separate | fără dublare inutilă, integritate mai bună, update parțial și SEO/editorial mai curate | implementare mai serioasă în Filament | **Ales** |

---

## 4. Recomandare arhitecturală fermă

### 4.1 Localizare și routing

Se va folosi **`niels-numbers/laravel-localizer`** ca strat unic de localizare a rutelor.

Puncte cheie care trebuie respectate în arhitectură:

- rutele sunt **înregistrate static**, nu sunt generate dinamic per request;
- pachetul suportă nativ **`php artisan route:cache`**;
- `Route::localize()` înregistrează automat **varianta cu prefix și varianta fără prefix**;
- pentru path-uri traduse se folosește **`Route::translate()` + `Localizer::url()`** și fișierele **`lang/{locale}/routes.php`**;
- pentru current page / canonical / hreflang se folosesc helper-ele de tip **`Route::localizedUrl()`**;
- pentru language switcher se folosește **`Route::localizedSwitcherUrl()`**;
- middleware-ul **`SetLocale` trebuie să ruleze înainte de `SubstituteBindings`**, iar `RedirectLocale` după `SetLocale`.

### 4.2 Traduceri de conținut

Toate entitățile editoriale principale vor folosi **tabele separate de traduceri** pe baza **`astrotomic/laravel-translatable`**:

- `Page` + `PageTranslation`
- `Article` + `ArticleTranslation`
- `Category` + `CategoryTranslation`
- `Tag` + `TagTranslation`

Atribute tipice traductibile:

- titlu;
- slug;
- excerpt / summary;
- body / content;
- meta title / meta description;
- câmpuri SEO editoriale.

### 4.3 Gestionarea coliziunii `page slug` vs `article slug` la root path

Aceasta este problema critică a arhitecturii publice.

Pentru că și paginile, și articolele trebuie să trăiască la rădăcină (`/{slug}`), **nu putem avea două rute dinamice separate cu aceeași semnătură**. Soluția recomandată este:

1. un singur **catch-all route de nivel root**, definit ultimul;
2. un **resolver central de rută publică**;
3. un tabel dedicat de registry, de exemplu **`localized_routes`**, cu unicitate pe `(locale, path)`.

Astfel:

- publicarea unei pagini sau a unui articol scrie/actualizează o intrare în `localized_routes`;
- la runtime, resolverul caută exact `locale + path`;
- dacă găsește `page`, trimite către controllerul de pagină;
- dacă găsește `article`, trimite către controllerul de articol;
- dacă nu găsește nimic, returnează 404.

Această abordare elimină ambiguitatea, permite validare editorială înainte de publish și previne coliziunile între pagini, articole și slug-uri rezervate de rute manuale.

### 4.4 Block builder

Recomandarea fermă este **să nu folosim Filament Builder cu persistență JSON brută pentru conținut public tradus**.

În schimb, folosim un **builder relațional**:

- structura blocurilor, ordinea și câmpurile neutre de limbă se salvează o singură dată;
- doar câmpurile textuale traductibile se salvează per limbă, separat;
- Filament oferă UX de builder, dar persistă în tabele relaționale, nu într-un singur blob JSON per limbă.

---

## 5. Modelare DB recomandată

## 5.1 Tabele principale de conținut

| Tabel | Rol | Coloane cheie |
|---|---|---|
| `pages` | entitate pagină | `id`, `status`, `template`, `is_home`, `published_at` |
| `page_translations` | traduceri pagină | `page_id`, `locale`, `title`, `slug`, `seo_title`, `seo_description` |
| `articles` | entitate articol | `id`, `status`, `published_at`, `author_id`, `featured_image_id` |
| `article_translations` | traduceri articol | `article_id`, `locale`, `title`, `slug`, `excerpt`, `body`, `seo_title`, `seo_description` |
| `categories` | arbore editorial | `id`, `parent_id`, `status`, `sort_order` |
| `category_translations` | traduceri categorie | `category_id`, `locale`, `name`, `slug`, `path`, `description` |
| `tags` | etichete | `id`, `status` |
| `tag_translations` | traduceri tag | `tag_id`, `locale`, `name`, `slug`, `description` |

### Indexare recomandată

- `unique(page_id, locale)`, `unique(article_id, locale)`, etc.
- `index(locale, slug)` pe tabelele de traduceri.
- `unique(locale, path)` pe `category_translations` pentru nesting și lookup rapid.
- `unique(locale, slug)` pe `tag_translations`.

## 5.2 Tabel de registry pentru rutele publice

| Tabel | Rol | Coloane cheie |
|---|---|---|
| `localized_routes` | registry unic pentru path-uri publice | `locale`, `path`, `routable_type`, `routable_id`, `route_name` |

### Reguli

- `unique(locale, path)` obligatoriu.
- aici intră cel puțin toate slug-urile root pentru `pages` și `articles`;
- opțional se pot introduce și path-urile materializate pentru alte entități publice;
- se rezervă explicit și slug-urile/path-urile definite manual în router.

## 5.3 Modelare recomandată pentru blocks și translations

### Structură neutră de limbă

| Tabel | Rol | Coloane cheie |
|---|---|---|
| `page_blocks` | instanță de block pe pagină | `id`, `page_id`, `type`, `sort_order`, `settings_json`, `is_active` |
| `page_block_items` | itemi repetați dintr-un block | `id`, `block_id`, `type`, `sort_order`, `settings_json` |

`settings_json` conține **doar date neutre de limbă**, de exemplu:

- image/media IDs;
- layout variant;
- număr coloane;
- background/theme;
- CTA target intern prin ID;
- booleans de afișare;
- ordine și configurare structurală.

### Câmpuri traductibile individuale

| Tabel | Rol | Coloane cheie |
|---|---|---|
| `page_block_translation_values` | texte traductibile la nivel de block | `block_id`, `locale`, `field_key`, `value` |
| `page_block_item_translation_values` | texte traductibile la nivel de item | `block_item_id`, `locale`, `field_key`, `value` |

### Exemple

Pentru un block `hero`:

- în `page_blocks.settings_json`: `image_id`, `alignment`, `overlay`, `cta_target_page_id`;
- în `page_block_translation_values`:  
  - `title`  
  - `subtitle`  
  - `description`  
  - `cta_label`

Pentru un block `features` cu itemi:

- în `page_block_items.settings_json`: `icon`, `image_id`, `link_target_id`;
- în `page_block_item_translation_values`:  
  - `title`  
  - `description`

### De ce aceasta este abordarea recomandată

- **evită dublarea inutilă**: imaginea, structura și layout-ul nu se copiază pentru fiecare limbă;
- **păstrează consistența**: schimbarea imaginii sau a structurii unui block afectează toate limbile corect;
- **păstrează performanța**: se încarcă doar traducerile pentru limba curentă, nu întregul blob al tuturor limbilor;
- **permite validare clară**: Filament poate valida separat câmpurile neutre și cele traductibile;
- **reduce riscul de drift editorial**: aceeași structură de pagină rămâne comună, iar doar textele diferă;
- **simplifică SEO și preview**: slug-urile, titlurile și textele sunt predictibile și indexabile corect.

### Observație importantă pentru Filament

Va fi necesar un **custom relational builder/editor**, nu persistența implicită a componentelor Builder în JSON. UX-ul poate rămâne “builder-like”, dar salvarea trebuie făcută prin servicii care împart datele între:

- `page_blocks`
- `page_block_items`
- `page_block_translation_values`
- `page_block_item_translation_values`

---

## 6. Routing și URL generation cu `niels-numbers/laravel-localizer`

## 6.1 Principii

1. **Rutele manuale cu același path în toate limbile**  
   se definesc cu `Route::localize()`.

2. **Rutele cu segmente traduse**  
   se definesc cu `Route::translate()` + `Localizer::url()` și chei în `lang/{locale}/routes.php`.

3. **Toate linkurile interne**  
   se generează prin named routes și `route()`, nu prin concatenare manuală de prefixe sau slug-uri.

4. **Ordinea middleware**  
   `SetLocale` trebuie să ruleze înainte de `SubstituteBindings`, altfel binding-ul pe slug tradus va eșua pe limba greșită.

## 6.2 Structură de rute recomandată

Ordinea publică recomandată:

1. home;
2. rute manuale statice;
3. rute cu path tradus pentru categorii/tag-uri/arhive;
4. root catch-all pentru pagini + articole.

### Exemple conceptuale

- `Route::localize()` pentru home și rute simple;
- `Route::translate()` pentru:
  - categorie: `category/{path}` -> `categorie/{path}` / altă traducere;
  - tag: `tag/{slug}` -> `eticheta/{slug}` / altă traducere;
- catch-all final: `/{slug}` sau `/{path}` doar pentru resolverul comun root.

### Notă despre `lang/{locale}/routes.php`

Pentru segmente traduse, cheia trebuie să fie **URI-ul complet relevant**, nu segmentat artificial. Asta este important mai ales la rute cu parametri și nesting.

## 6.3 Canonical / hreflang vs switcher de limbă

Diferența trebuie tratată explicit:

- **canonical + alternate hreflang**: folosesc URL-uri **canonice**, deci `Route::localizedUrl()`;
- **language switcher**: folosește `Route::localizedSwitcherUrl()`.

De ce:

- `localizedUrl()` poate întoarce forma fără prefix pentru limba implicită ascunsă; aceasta este forma corectă pentru SEO;
- `localizedSwitcherUrl()` emite forma cu prefix chiar și pentru limba implicită, ca alegerea limbii să fie explicită în URL; apoi `RedirectLocale` poate canonicaliza către forma finală.

### Implicație importantă

- în pagină curentă: pentru canonical/hreflang folosim `Route::localizedUrl()`;
- în switcher: folosim `Route::localizedSwitcherUrl()`;
- în sitemap generat din console/job: **nu folosim `Route::localizedUrl()`**, fiindcă este helper dependent de requestul curent; acolo generăm explicit URL-urile canonice din named routes + locale + slug/path tradus.

## 6.4 Linkuri CMS-generated

Pentru entitățile generate din CMS:

- `Page` și `Article` folosesc resolverul de root + registry;
- `Category` și `Tag` folosesc named routes cu prefix/path tradus;
- serviciul de URL generation trebuie să fie centralizat, ca frontend-ul, sitemap-ul, schema.org și admin preview-ul să genereze aceeași adresă.

---

## 7. Pași de implementare

1. **Creare proiect nou și instalare bază**
   - pornește de la zero cu `laravel new`;
   - instalează dependențele de bază ale proiectului;
   - apoi adaugă `niels-numbers/laravel-localizer` și pachetele necesare pentru CMS.

2. **Configurare middleware și locale**
   - definire `supported_locales`;
   - setare `hide_default_locale`;
   - mutare `SetLocale` și `RedirectLocale` înainte/după `SubstituteBindings` în ordinea corectă.

3. **Refactor rutare publică**
   - separare între rute localizate simple și rute cu path tradus;
   - introducere catch-all root unic pentru pagini și articole;
   - rezervare explicită a slug-urilor/path-urilor statice.

4. **Modelare și migrare DB pentru conținut**
   - tabele principale + tabele de traduceri cu `astrotomic/laravel-translatable`;
   - migrare pentru `localized_routes`.

5. **Modelare și migrare DB pentru block builder**
   - tabele relaționale pentru blocks, items și translation values;
   - definirea unui registry PHP al tipurilor de block și al field-urilor neutre/traductibile.

6. **Implementare admin Filament**
   - formulare translatabile pentru pagini, articole, categorii, tag-uri;
   - editor custom pentru blocks cu persistență relațională;
   - validare de unicitate pe slug/path per limbă.

7. **Resolver public și route-model resolution**
   - serviciu unic de rezolvare `locale + path` -> entitate;
   - binding/rezolvare corectă pentru slugs și path-uri traduse.

8. **SEO și schema**
   - canonical + alternate hreflang;
   - sitemap dinamic cu `xhtml:link rel="alternate" hreflang`;
   - generator schema.org per tip de conținut prin `spatie/schema-org`.

9. **Language switcher**
   - switcher pe aceeași pagină logică;
   - fallback controlat dacă lipsește traducerea pentru entitatea curentă.

10. **Hardening și testare**
   - `route:cache`;
   - toate testele sunt integration testing în browser headless cu Pest v4 Browser Testing, fără unit tests.
   - comenzi de instalare/execuție de reținut:
     - `composer require pestphp/pest-plugin-browser --dev`
     - `npm install playwright@latest`
     - `npx playwright install`

---

## 8. Fișiere / părți de cod afectate

- proiectul creat de `laravel new` (scaffold inițial, `.env`, `artisan`, `bootstrap/`, `config/`, `routes/`)
- `composer.json`
- `package.json`
- `config/localizer.php`
- `config/app.php`
- `bootstrap/app.php` sau `app/Http/Kernel.php`
- `routes/web.php`
- `lang/en/routes.php`, `lang/ro/routes.php`, etc.
- migrații pentru:
  - pagini / articole / categorii / tag-uri + translations
  - `localized_routes`
  - `page_blocks`, `page_block_items`
  - `page_block_translation_values`, `page_block_item_translation_values`
- modele Eloquent și clasele de translation
- servicii de URL generation / route resolving
- controllere publice
- resurse Filament / pagini custom Filament / componente Livewire
- strat SEO: sitemap, hreflang, canonical, schema.org
- view components / frontend shared data pentru language switcher
- suite de browser tests în `tests/Browser/` și config-ul aferent Pest v4 / Playwright

---

## 9. Validare / teste

## 9.1 Teste de routing

- rutele localizate sunt înregistrate static;
- `php artisan route:cache` funcționează;
- `route()` generează URL corect cu și fără prefix pentru limba implicită;
- `Route::translate()` produce path-urile traduse conform `lang/{locale}/routes.php`.

## 9.2 Teste de conținut tradus

- `Page`, `Article`, `Category`, `Tag` citesc și persistă corect traducerile în tabele separate;
- slug-urile sunt unice per limbă;
- binding/resolverul folosește limba corectă.

## 9.3 Teste pentru coliziuni root path

- nu se poate publica articol și pagină cu același slug în aceeași limbă;
- slug-urile rezervate de rutele manuale nu pot fi folosite editorial;
- resolverul root trimite către tipul corect de conținut.

## 9.4 Teste block builder

- modificarea imaginii sau structurii unui block nu creează copii per limbă;
- modificarea titlului/descrierii afectează doar limba editată;
- itemii repetați își păstrează ordinea și structura comună între limbi.

## 9.5 Teste SEO

- fiecare pagină are canonical corect;
- fiecare pagină are `alternate hreflang` corect pentru limbile disponibile;
- sitemap-ul conține URL-urile canonice și `xhtml:link rel="alternate" hreflang`;
- schema.org reflectă tipul de conținut și limba curentă.

## 9.6 Teste de switcher

- schimbarea limbii duce la aceeași pagină logică;
- switcherul folosește `localizedSwitcherUrl()`, nu URL-ul canonical;
- pentru traduceri lipsă există comportament explicit: fie limbă indisponibilă în switcher, fie redirect controlat conform regulii stabilite.

---

## 10. Decizii fixate

### 10.1 `main_lang_prefix`
- `main_lang_prefix` determină dacă limba principală este expusă în URL.
- Pentru `en` principal și `fr` secundar, un articol public are următoarele variante de rută:
  - `/article1`
  - `/en/article1`
  - `/fr/article1`
- Regula de redirect este:
  - dacă `main_lang_prefix = true`, `/article1` → 301 → `/en/article1`
  - dacă `main_lang_prefix = false`, `/en/article1` → 301 → `/article1`

### 10.2 Conținut publicat fără traduceri
- Un articol poate fi publicat fără traduceri.
- Dacă traducerea pentru limba curentă lipsește:
  - website-ul afișează conținutul din limba principală;
  - admin-ul afișează conținutul din limba principală;
  - URL-ul folosește slug-ul din limba principală.
- Exemplu: articol tradus doar în engleză → varianta franceză este `/fr/slug-en`.

### 10.3 Rute manuale
- Rutele manuale au prioritate peste ruta dinamică `/{slug}`.
- Nu se introduce logică specială de conflict handling pentru ele.

### 10.4 Categorie nesting
- Recalcularea path-urilor la mutarea categoriei este sincronă.
- Nu se folosește job async în v1.

### 10.5 Block builder
- Blocurile și conținutul blocurilor nu se traduc în v1.
- Traducerea blocurilor rămâne pentru o iterație ulterioară.

### 10.6 Cache
- Cache-ul aplicației nu intră în v1 pentru conținutul asamblat.
- Poate fi adăugat ulterior fără schimbarea modelului de date.

### 10.7 Sitemap
- Sitemap-ul include URL-urile publicabile reale.
- Dacă un articol/pagină are fallback pe slug-ul limbii principale, sitemap-ul include fallback-ul respectiv.
- Exemplu pentru conținut tradus doar în engleză:
  - `/en/example-blog`
  - `/fr/example-blog`

### 10.8 Canonical / hreflang / switcher
- `canonical` și `hreflang` folosesc URL-ul SEO-canonical.
- `switcher` folosește URL-ul explicit de schimbare limbă.
- Cele două nu se amestecă.
- Exemplu:
  - canonical curent: `/fr/example-blog`
  - switcher pentru engleză: `/en/example-blog` sau forma canonicală a limbii principale, în funcție de `main_lang_prefix`.
