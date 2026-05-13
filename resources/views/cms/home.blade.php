<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home</title>
    @if(!empty($seo['canonical'] ?? null))
        <link rel="canonical" href="{{ $seo['canonical'] }}">
    @endif
</head>
<body>
    <main>
        <h1>{{ $page?->titleForLocale() ?? 'CMS Home' }}</h1>
        <p>{{ $page?->bodyForLocale() ?? 'Homepage' }}</p>
    </main>
</body>
</html>
