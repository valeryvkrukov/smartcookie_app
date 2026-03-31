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
        <link href="https://cdn.jsdelivr.net/npm/ti-icons@0.1.2/css/themify-icons.min.css" rel="stylesheet">


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [class^="ti-"], [class*=" ti-"] {
                font-family: 'themify' !important;
                speak: none;
                font-style: normal;
                font-weight: normal;
                font-variant: normal;
                text-transform: none;
                line-height: 1;
                -webkit-font-smoothing: antialiased;
            }
        </style>
        <style>
            /* Themify */
            .ti-calendar, .ti-user, .ti-timer, .ti-money, .ti-write, [class^="ti-"], [class*=" ti-"] {
                font-family: 'themify' !important;
                display: inline-block;
                speak: none;
                font-style: normal;
                font-weight: normal;
                font-variant: normal;
                text-transform: none;
                line-height: 1;
                -webkit-font-smoothing: antialiased;
                font-size: 1.5rem; 
            }
        </style>
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

        <!--script>
            window.submitModalForm = function(buttonElement) {
                const form = buttonElement.closest('form');
                const formData = new FormData(form);
                
                // visual feedback
                buttonElement.disabled = true;
                const originalText = buttonElement.innerText;
                buttonElement.innerHTML = '<span class="inline-flex items-center justify-center"><i class="ti-reload animate-spin mr-3 text-sm flex-shrink-0" style="line-height: 1; display: inline-block;"></i> PROCESSING...</span>';

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(async response => {
                    const data = await response.json();
                    if (response.ok && data && data.success) {
                        window.dispatchEvent(new CustomEvent('close-modal'));
                    } else {
                        // IMPORTANT: send object exactly as Alpine expects
                        window.dispatchEvent(new CustomEvent('set-error', { 
                            detail: { message: data.message || 'Validation error' } 
                        }));
                    }
                })
                .then(data => {
                    window.dispatchEvent(new CustomEvent('close-modal'));

                    if (window.calendar) {
                        window.calendar.removeAllEvents();
                        window.calendar.refetchEvents();
                    } else {
                        window.location.reload();
                    }
                })
                .catch(err => {
                    window.dispatchEvent(new CustomEvent('set-error', { 
                        detail: { message: 'Network or Server Error' } 
                    }));
                })
                .finally(() => {
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalText;
                });
            }
        </script-->
    </body>
</html>
