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
    <body class="bg-base font-sans text-body font-regular text-ink antialiased min-h-screen relative overflow-x-hidden selection:bg-accent selection:text-white">
        <!-- Ambient Background Circles matching design mockup -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0" aria-hidden="true">
            <div class="absolute top-[6%] -left-[180px] w-[440px] h-[440px] rounded-full bg-[#131C2E]"></div>
            <div class="absolute top-[5%] -right-[100px] w-[320px] h-[320px] rounded-full bg-[#131C2E]"></div>
            <div class="absolute -bottom-[60px] -left-[80px] w-[360px] h-[360px] rounded-full bg-[#131C2E]"></div>
            <div class="absolute -bottom-[100px] -right-[80px] w-[420px] h-[420px] rounded-full bg-[#131C2E]"></div>
        </div>

        <div class="min-h-screen flex flex-col relative z-10">
            <!-- Unified Reusable Navbar Component -->
            <x-navbar />

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-surface-1 border-b border-subtle">
                    <div class="max-w-content mx-auto py-16 px-16 md:px-24">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 flex flex-col">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
