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
        @php
            $component = $page['component'];
            if (str_starts_with($component, 'Modules::')) {
                $parts = explode('/', str_replace('Modules::', '', $component), 2);
                $moduleName = $parts[0];
                $pagePath = $parts[1] ?? 'Index';
                $componentPath = "Modules/{$moduleName}/resources/assets/js/Pages/{$pagePath}.jsx";
            } else {
                $componentPath = "resources/js/Pages/{$component}.jsx";
            }
        @endphp
        @vite(['resources/js/app.jsx', $componentPath])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
