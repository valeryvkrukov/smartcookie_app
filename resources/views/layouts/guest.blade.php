<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-[#F9FAFB]">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <!-- Logo -->
            <div class="mt-10 mb-10 text-center">
                <a href="/" class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-[#212120] rounded-[1.5rem] flex items-center justify-center shadow-2xl mb-4">
                        <span class="text-white font-black text-3xl">S</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">SmartCookie <span class="text-indigo-600">Tutors</span></h1>
                </a>
            </div>

            <!-- Card -->
            <div class="w-full sm:max-w-md mt-6 px-10 py-12 bg-white shadow-[0_35px_60px_-15px_rgba(0,0,0,0.05)] border border-slate-100 rounded-[3.5rem]">
                {{ $slot }}
            </div>

            <p class="mt-10 text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em]">Built for Excellence • 2026</p>
        </div>
    </body>

</html>
