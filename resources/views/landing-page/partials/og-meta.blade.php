{{--
    Meta Open Graph + Twitter Card (pratinjau link di WhatsApp, Facebook, Telegram, X, dll.)
    Dipakai dari dalam @push('styles') karena stack itu sudah dirender di <head> layout.

    Variabel:
      $ogTitle        (wajib)
      $ogDescription  (wajib)
      $ogUrl          (wajib) alamat kanonik halaman ini
      $ogImage        (opsional) URL gambar; default logo PUK OMI
      $ogType         (opsional) 'website' (default) atau 'article'
      $ogImageAlt     (opsional) default = $ogTitle
      $ogPublished    (opsional) waktu terbit ISO 8601, khusus article
      $ogSection      (opsional) kategori, khusus article
      $ogTags         (opsional) array nama tag, khusus article
--}}
@php
    $ogType  = $ogType ?? 'website';
    $ogImage = $ogImage ?? asset('assets/media.png');

    // Crawler hanya mau URL absolut
    if (!preg_match('#^https?://#i', $ogImage)) {
        $ogImage = url($ogImage);
    }

    $ogAlt  = $ogImageAlt ?? $ogTitle;
    $ogMime = match (strtolower(pathinfo(parse_url($ogImage, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png'         => 'image/png',
        'webp'        => 'image/webp',
        'gif'         => 'image/gif',
        default       => null,
    };
@endphp

<link rel="canonical" href="{{ $ogUrl }}">

<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="PUK OMRON">
<meta property="og:locale" content="id_ID">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:image" content="{{ $ogImage }}">
@if ($ogMime)
    <meta property="og:image:type" content="{{ $ogMime }}">
@endif
<meta property="og:image:alt" content="{{ $ogAlt }}">

@if ($ogType === 'article')
    @if (!empty($ogPublished))
        <meta property="article:published_time" content="{{ $ogPublished }}">
    @endif
    @if (!empty($ogSection))
        <meta property="article:section" content="{{ $ogSection }}">
    @endif
    @foreach (($ogTags ?? []) as $ogTag)
        <meta property="article:tag" content="{{ $ogTag }}">
    @endforeach
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">