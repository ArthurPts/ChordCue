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
    <body class="bg-base font-sans text-body font-regular text-ink antialiased min-h-screen flex items-center justify-center p-16 relative overflow-x-hidden selection:bg-accent selection:text-white">
        <!-- Ambient Background Circles matching design mockup -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0" aria-hidden="true">
            <!-- Top Left Circle -->
            <div class="absolute top-[6%] -left-[180px] w-[440px] h-[440px] rounded-full bg-[#131C2E]"></div>
            <!-- Top Right Circle -->
            <div class="absolute top-[5%] -right-[100px] w-[320px] h-[320px] rounded-full bg-[#131C2E]"></div>
            <!-- Bottom Left Circle -->
            <div class="absolute -bottom-[60px] -left-[80px] w-[360px] h-[360px] rounded-full bg-[#131C2E]"></div>
            <!-- Bottom Right Circle -->
            <div class="absolute -bottom-[100px] -right-[80px] w-[420px] h-[420px] rounded-full bg-[#131C2E]"></div>
        </div>

        <!-- Auth Card Container -->
        <div class="relative z-10 w-full max-w-[460px] bg-surface-1 border border-subtle rounded-2xl p-24 sm:p-36">
            {{ $slot }}
        </div>
    </body>
</html>
