{{-- Gamarketplace — layout for pages without the sidebar (login, logout, admin login). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'GaMarketplace')</title>
    <meta name="description" content="@yield('description', 'A secondhand marketplace for UGM students.')" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('marketplace/css/base.css') }}" />
    @stack('styles')

    <script src="https://cdn.jsdelivr.net/npm/lucide@0.383.0/dist/umd/lucide.min.js" defer></script>
    <script src="{{ asset('marketplace/js/store.js') }}" defer></script>
    <script src="{{ asset('marketplace/js/common.js') }}" defer></script>
    @stack('scripts')
</head>
<body>
    @yield('content')

    <div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
</body>
</html>
