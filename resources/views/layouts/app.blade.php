<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SmartCookie Tutors') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

    </head>
    <body class="font-sans antialiased bg-[#F9FAFB]">
        <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
            @include('layouts.sidebar')

            {{-- Mobile backdrop overlay --}}
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-opacity ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="sidebarOpen = false"
                 class="sm:hidden fixed inset-0 z-30 bg-slate-900/60 backdrop-blur-sm"
                 style="display: none;"></div>

            <!-- Main Content Area -->
            <main class="flex-1 ml-0 sm:ml-20 transition-all duration-500 p-5 sm:p-8 lg:p-12">

                {{-- Mobile top bar with hamburger --}}
                <div class="sm:hidden flex items-center justify-between mb-6 -mx-5 -mt-5 px-5 py-4 bg-white border-b border-slate-100 shadow-sm">
                    <div class="w-8 h-8 bg-indigo-600 rounded-xl flex items-center justify-center">
                        <span class="text-white font-black text-sm">S</span>
                    </div>
                    <button @click="sidebarOpen = true"
                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors">
                        <i class="ti-menu text-slate-700 text-lg"></i>
                    </button>
                </div>

                <!-- Header section -->
                <div class="mb-8 sm:mb-12 flex justify-between items-end">
                    <div>
                        <p class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.3em] mb-2">{{ date('l, M d') }}</p>
                        <h1 class="text-4xl font-black text-slate-900 tracking-tight">{{ $header_title ?? 'Dashboard' }}</h1>
                    </div>
                    <!-- Here can be some Quick Action buttons -->
                </div>

                {{ $slot }}
            </main>
        </div>

        <x-modal-container />
        <x-delete-modal />

        <script>
        // ── Session guard ──────────────────────────────────────────────────────────
        // 1. Global fetch interceptor: any 419 (expired CSRF) → redirect to login
        (function () {
            var _orig = window.fetch;
            window.fetch = function () {
                return _orig.apply(this, arguments).then(function (response) {
                    if (response.status === 419) {
                        window.location.href = '/login';
                    }
                    return response;
                });
            };
        })();

        // 2. Heartbeat every 30 min — keeps the session alive while the tab is open
        //    Also refreshes the CSRF meta tag so modal fetch calls always use a valid token
        setInterval(function () {
            fetch('/ping', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (data && data.csrf) {
                        var el = document.querySelector('meta[name="csrf-token"]');
                        if (el) el.setAttribute('content', data.csrf);
                    }
                })
                .catch(function () {});
        }, 30 * 60 * 1000); // every 30 minutes
        // ──────────────────────────────────────────────────────────────────────────
        </script>

        @stack('scripts')
    </body>
</html>
