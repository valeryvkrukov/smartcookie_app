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
        <div class="min-h-screen flex">
            @include('layouts.sidebar')

            <!-- Main Content Area -->
            <main class="flex-1 ml-20 transition-all duration-500 p-8 lg:p-12">
                <!-- Header section -->
                <div class="mb-12 flex justify-between items-end">
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

        @stack('scripts')
    </body>
</html>
