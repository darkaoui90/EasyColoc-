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
        <div class="app-shell" x-data="{ menuOpen: false }">
            <aside class="app-sidebar">
                <div class="flex items-center justify-between px-6 py-5 md:py-7">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-[34px] font-extrabold leading-none text-[#4f47e5]">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#eef0ff] text-sm">EC</span>
                        <span class="text-3xl">EasyColoc</span>
                    </a>
                    <button
                        type="button"
                        class="btn-secondary px-3 py-2 md:hidden"
                        @click="menuOpen = !menuOpen"
                    >
                        Menu
                    </button>
                </div>

                <nav class="hidden space-y-1 px-4 pb-4 md:block" :class="{ 'block': menuOpen, 'hidden': !menuOpen }">
                    <a href="{{ route('dashboard') }}" class="left-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span>D</span>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('colocations.index') }}" class="left-nav-link {{ request()->routeIs('colocations.*') ? 'active' : '' }}">
                        <span>C</span>
                        <span>Colocations</span>
                    </a>

                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('admin.index') }}" class="left-nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                            <span>A</span>
                            <span>Admin</span>
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="left-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <span>P</span>
                        <span>Profile</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="pt-1">
                        @csrf
                        <button class="left-nav-link w-full text-left" type="submit">
                            <span>L</span>
                            <span>Logout</span>
                        </button>
                    </form>
                </nav>

                <div class="mx-4 mb-4 mt-8 hidden rounded-2xl border border-[#1b2a57] bg-gradient-to-br from-[#0d1530] to-[#091128] p-4 text-white md:block">
                    <p class="text-[11px] uppercase tracking-[0.16em] text-[#8ea3da]">Votre reputation</p>
                    <p class="mt-1 text-3xl font-bold leading-none">{{ auth()->user()->reputation > 0 ? '+' : '' }}{{ auth()->user()->reputation }} <span class="text-base font-medium">points</span></p>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                        @php($progress = max(8, min(100, 50 + auth()->user()->reputation * 4)))
                        <div class="h-full rounded-full bg-[#1dd6a2]" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            </aside>

            <div class="app-main">
                <header class="border-b border-[#e7ecf4] bg-white/70 px-6 py-4 backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-[#2b3140] md:text-[26px] md:normal-case md:tracking-normal">
                            @isset($header)
                                {{ $header }}
                            @else
                                Dashboard
                            @endisset
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="hidden text-right md:block">
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#1f2b45]">{{ strtoupper(auth()->user()->name) }}</p>
                                <p class="badge-online">Online</p>
                            </div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#0d1530] text-sm font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        </div>
                    </div>
                </header>

                <main class="p-5 md:p-8">
                    @if (session('success'))
                        <div class="mb-4 rounded-xl border border-[#bcebd7] bg-[#e8fbf2] px-4 py-3 text-sm font-semibold text-[#15895f]">
                            - {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-xl border border-[#ffd2dd] bg-[#fff1f5] px-4 py-3 text-sm font-semibold text-[#d22a58]">
                            - {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-xl border border-[#ffd2dd] bg-[#fff1f5] px-4 py-3 text-sm text-[#d22a58]">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
