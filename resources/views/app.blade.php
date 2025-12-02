<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ settings('app_name', config('app.name', 'Laravel')) }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead

        <style>
            #global-loading {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: hsl(var(--background) / 0.8);
                backdrop-filter: blur(4px);
            }
            #global-loading.hidden {
                display: none;
            }
            .global-loading-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1.5rem;
            }
            .global-spinner {
                position: relative;
                width: 48px;
                height: 48px;
            }
            .global-spinner::before {
                content: '';
                position: absolute;
                inset: 0;
                border-radius: 50%;
                border: 4px solid hsl(var(--primary) / 0.1);
            }
            .global-spinner::after {
                content: '';
                position: absolute;
                inset: 0;
                border-radius: 50%;
                border: 4px solid transparent;
                border-top-color: hsl(var(--primary));
                border-right-color: hsl(var(--primary) / 0.5);
                animation: spin 1s linear infinite;
            }
            .global-loading-text {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }
            .global-loading-text p {
                margin: 0;
                font-size: 0.875rem;
                line-height: 1.25rem;
            }
            .global-loading-text p:first-child {
                font-weight: 500;
                color: hsl(var(--foreground));
            }
            .global-loading-text p:last-child {
                font-size: 0.75rem;
                color: hsl(var(--muted-foreground));
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div id="global-loading">
            <div class="global-spinner" role="status" aria-label="Loading"></div>
        </div>
        @inertia
    </body>
</html>
