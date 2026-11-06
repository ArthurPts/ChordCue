<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ChordCue') }}</title>

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-base font-sans text-body font-regular text-ink antialiased min-h-screen flex items-center justify-center p-16 relative overflow-x-hidden selection:bg-accent selection:text-accent-on">
        <!-- Auth Card Container -->
        <div class="relative z-10 w-full max-w-[460px] bg-surface-1 border border-subtle rounded-lg p-24 sm:p-32 shadow-2xl">
            {{ $slot }}
        </div>
    </body>
</html>
