<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @filamentStyles
    @vite(['resources/css/app.css', 'resources/css/filament/admin/theme.css'])
</head>
<body class="bg-white antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    @livewire('notifications') {{-- Only required if you wish to send flash notifications --}}

    @filamentScripts
    @vite('resources/js/app.js')
    @stack('scripts')
</body>
</html>
