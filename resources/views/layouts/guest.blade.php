<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'EasyColoc') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="flex min-h-screen items-center justify-center bg-[#f1f4f9] px-4">
            <div class="w-full max-w-md">
                <div class="mb-5 text-center">
                    <a href="/" class="inline-flex items-center gap-2 text-5xl font-extrabold leading-none text-[#4f47e5]">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#eef0ff] text-sm">EC</span>
                        <span>EasyColoc</span>
                    </a>
                </div>

                <div class="panel p-6 md:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
