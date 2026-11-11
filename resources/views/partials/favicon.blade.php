{{--
    Favicon partial — include in every <head> section.

    Asset strategy (progressive enhancement):
      1. favicon.ico       — legacy browsers (IE11, older Firefox/Chrome)
      2. favicon-32x32.png — modern browsers that prefer PNG
      3. favicon.svg       — Chromium 80+, Firefox 41+, Safari 17+ (scales perfectly)
      4. apple-touch-icon  — iOS Safari home-screen / bookmark icon (180×180 px)
      5. theme-color       — Android Chrome tab colour + PWA toolbar tint (#0f766e = brand teal)

    All three raster assets (ICO, PNG, Apple touch) are teal (#0f766e) rounded-square
    placeholders until the full Spa Alexandria logo PNG is uploaded to Hostinger.
    Once uploaded, regenerate these assets from the logo for maximum fidelity.
--}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('favicon-32x32.png') }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<meta name="theme-color" content="#0f766e">
