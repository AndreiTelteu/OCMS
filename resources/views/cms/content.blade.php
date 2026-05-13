<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] ?? class_basename($model) }}</title>
    @if(!empty($seo['canonical'] ?? null))
        <link rel="canonical" href="{{ $seo['canonical'] }}">
    @endif
    @foreach(($seo['alternates'] ?? []) as $locale => $url)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
    @endforeach
    @if(!empty($schema ?? null))
        {!! $schema !!}
    @endif
</head>
<body>
    @if(!empty($seo['switcher'] ?? []))
        <nav aria-label="Language switcher">
            @foreach($seo['switcher'] as $locale => $url)
                <a href="{{ $url }}" hreflang="{{ $locale }}">{{ strtoupper($locale) }}</a>
            @endforeach
        </nav>
    @endif
    <main>
        <h1>
            {{ method_exists($model, 'titleForLocale') ? $model->titleForLocale() : ($model->nameForLocale() ?? class_basename($model)) }}
        </h1>
        <article>
            @php
                $body = null;
                foreach (['bodyForLocale', 'excerptForLocale', 'descriptionForLocale'] as $method) {
                    if (method_exists($model, $method)) {
                        $body = $model->{$method}();
                        if ($body) {
                            break;
                        }
                    }
                }
            @endphp
            {{ $body ?? '' }}
        </article>
    </main>
</body>
</html>
