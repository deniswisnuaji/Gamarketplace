{{--
    Gamarketplace — main layout (pages with the sidebar).
    Converted from the approved HTML/CSS/JS design in frontend-original/.
    Assets live in public/marketplace/ and load with asset(), so this layout does NOT use the
    Inertia/Vue/Tailwind bundle (resources/css/app.css) and can't change those styles.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'GaMarketplace')</title>
    <meta name="description" content="@yield('description', 'A secondhand marketplace for UGM students.')" />

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('marketplace/css/base.css') }}" />
    @stack('styles')

    <!-- Icons + scripts (deferred: they run in this order after the page loads) -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.383.0/dist/umd/lucide.min.js" defer></script>
    <script src="{{ asset('marketplace/js/store.js') }}" defer></script>
    <script src="{{ asset('marketplace/js/common.js') }}" defer></script>
    <script src="{{ asset('marketplace/js/layout.js') }}" defer></script>
    @stack('scripts')
</head>
<body class="@yield('body_class')">
    @yield('before_sidebar')

    @include('marketplace.partials.sidebar', ['active' => trim($__env->yieldContent('active'))])

    <!-- ================= PAGE ================= -->
    <div class="page">
        @include('marketplace.partials.topbar')

        @yield('content')
    </div>

    @yield('after_page')

    <!-- Toast message -->
    <div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
</body>
</html>
