@props(['seo' => null])
<!DOCTYPE html>
<html lang="ka">
@php
    $settings = \App\Models\SiteSetting::current();
    $brand = $settings->meta_title ?: 'Ronistationery';
    // Page-level SEO (e.g. a product) overrides site defaults; both optional.
    $metaDescription = $seo['description'] ?? $settings->meta_description;
    $metaKeywords = $seo['keywords'] ?? null;
    $ogTitle = $seo['title'] ?? ($title ?? $brand);
    $ogType = $seo['og_type'] ?? 'website';
    $ogImage = $seo['og_image'] ?? ($settings->logo ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo) : null);
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $brand }} — {{ $brand }}</title>
    @if($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if($metaKeywords)
        <meta name="keywords" content="{{ $metaKeywords }}">
    @endif
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    @if($metaDescription)
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    @if($ogImage)
        <meta property="og:image" content="{{ url($ogImage) }}">
    @endif
    <link rel="icon" type="image/png" href="/favicon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-white">
    <x-storefront.nav />

    <main id="main" class="min-h-[60vh]">
        {{ $slot }}
    </main>

    <x-storefront.footer />

    {{-- Global toast (add-to-cart confirmations, errors) --}}
    <div x-data="{ show: false, message: '', type: 'success', _t: null }"
         @notify.window="message = $event.detail.message; type = $event.detail.type || 'success'; show = true; clearTimeout(_t); _t = setTimeout(() => show = false, 2800)"
         x-show="show" x-cloak x-transition.opacity.duration.200ms
         class="fixed bottom-5 left-1/2 -translate-x-1/2 z-50 pointer-events-none">
        <div :class="type === 'error' ? 'bg-red-600' : 'bg-ink'"
             class="text-white text-sm px-4 py-2.5 rounded-full shadow-card-hover flex items-center gap-2">
            <svg x-show="type === 'success'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span x-text="message"></span>
        </div>
    </div>

    @livewireScripts
</body>
</html>
