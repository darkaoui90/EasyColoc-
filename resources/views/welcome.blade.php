<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>EasyColoc</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <main class="flex min-h-screen items-center justify-center bg-[#f1f4f9] px-4">
            <div class="w-full max-w-2xl panel p-10 text-center">
                <div class="inline-flex items-center gap-2 text-6xl font-extrabold text-[#4f47e5]">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[#eef0ff] text-base">EC</span>
                    <span>EasyColoc</span>
                </div>

                <h1 class="mt-6 text-4xl font-extrabold text-[#1f2738]">Gestion simple de colocation</h1>
                <p class="mx-auto mt-3 max-w-xl text-base text-[#6f7b94]">
                    Suivez les depenses communes, visualisez les dettes et gerez votre colocation sans calculs manuels.
                </p>

                <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary">Acceder au dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary">Se connecter</a>
                        <a href="{{ route('register') }}" class="btn-secondary">Creer un compte</a>
                    @endauth
                </div>
            </div>
        </main>
    </body>
</html>
