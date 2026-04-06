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
    <body class="font-sans antialiased bg-[#F9FAFB] overflow-x-hidden">
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
            <main class="flex-1 min-w-0 ml-0 sm:ml-20 transition-all duration-500 p-5 sm:p-8 lg:p-12">

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
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">{{ $header_title ?? 'Dashboard' }}</h1>
                    </div>
                    <!-- Here can be some Quick Action buttons -->
                </div>

                {{ $slot }}
            </main>
        </div>

        <x-modal-container />
        <x-delete-modal />

        {{-- ── Session expired banner ──────────────────────────────────────────── --}}
        <div id="session-expired-banner"
             class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/70 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl px-8 py-7 max-w-sm w-full mx-4 text-center space-y-4">
                <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mx-auto">
                    <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-black text-slate-900">Session Expired</h2>
                <p class="text-sm text-slate-500">Your session has expired due to inactivity. Please log in again to continue.</p>
                <a href="/login"
                   class="block w-full py-3 rounded-2xl bg-[#212120] text-white font-bold text-sm hover:bg-slate-700 transition-colors">
                    Log In Again
                </a>
            </div>
        </div>

        <script>
        // ── Session guard ──────────────────────────────────────────────────────────

        function _pingSession() {
            fetch('/ping', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (data && data.csrf) {
                        var el = document.querySelector('meta[name="csrf-token"]');
                        if (el) el.setAttribute('content', data.csrf);
                    }
                })
                .catch(function () {});
        }

        // 1. Global fetch interceptor: any 419 → show session-expired banner
        (function () {
            var _orig = window.fetch;
            window.fetch = function () {
                return _orig.apply(this, arguments).then(function (response) {
                    if (response.status === 419) {
                        document.getElementById('session-expired-banner').classList.remove('hidden');
                    }
                    return response;
                });
            };
        })();

        // 2. Heartbeat every 10 min while tab is active
        setInterval(_pingSession, 10 * 60 * 1000);

        // 3. Ping immediately when user returns to the tab (fixes browser timer freeze)
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') _pingSession();
        });
        // ──────────────────────────────────────────────────────────────────────────
        </script>

        @stack('scripts')
    </body>
</html>
