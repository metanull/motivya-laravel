{{-- Basic OG / SEO meta tags --}}
<meta name="description" content="{{ $description ?? config('app.name') }}">
<meta name="robots" content="{{ $robots ?? 'index, follow' }}">

{{-- Open Graph --}}
<meta property="og:type" content="{{ $type ?? 'website' }}">
<meta property="og:title" content="{{ $title ?? config('app.name') }}">
<meta property="og:description" content="{{ $description ?? config('app.name') }}">
<meta property="og:url" content="{{ $url ?? url()->current() }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
@isset($image)
    <meta property="og:image" content="{{ $image }}">
@endisset

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $title ?? config('app.name') }}">
<meta name="twitter:description" content="{{ $description ?? config('app.name') }}">
@isset($image)
    <meta name="twitter:image" content="{{ $image }}">
@endisset
