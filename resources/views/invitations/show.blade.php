<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Invitation - EasyColoc</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <main class="flex min-h-screen items-center justify-center bg-[#f1f4f9] px-4">
            <div class="w-full max-w-md">
                <div class="mb-6 text-center">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-5xl font-extrabold text-[#4f47e5]">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#eef0ff] text-sm">EC</span>
                        <span>EasyColoc</span>
                    </a>
                </div>

                <div class="panel p-7 text-center">
                    <div class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-[#eef0ff] text-base font-bold text-[#4f47e5]">INV</div>

                    <h1 class="mt-4 text-3xl font-extrabold">Invitation colocation</h1>
                    <p class="mt-1 text-sm text-[#7f8aa2]">
                        Vous etes invite a rejoindre <span class="font-bold text-[#4f47e5]">{{ $invitation->colocation->name }}</span>.
                    </p>
                    <p class="mt-1 text-xs text-[#95a1b8]">Invitation envoyee a: {{ $invitation->email }}</p>

                    @if ($isExpired)
                        <div class="mt-5 rounded-xl border border-[#ffd5df] bg-[#fff2f6] px-4 py-3 text-sm font-semibold text-[#d32758]">
                            Cette invitation a expire.
                        </div>
                        <a href="{{ route('dashboard') }}" class="btn-secondary mt-4 w-full">Retour</a>
                    @elseif ($alreadyHandled)
                        <div class="mt-5 rounded-xl border border-[#d3f3e7] bg-[#edfff7] px-4 py-3 text-sm font-semibold text-[#159366]">
                            Cette invitation a deja ete acceptee.
                        </div>
                        <a href="{{ route('dashboard') }}" class="btn-secondary mt-4 w-full">Retour</a>
                    @elseif (!$emailMatches)
                        <div class="mt-5 rounded-xl border border-[#ffd5df] bg-[#fff2f6] px-4 py-3 text-sm font-semibold text-[#d32758]">
                            Cette invitation correspond a un autre email.
                        </div>
                        <a href="{{ route('dashboard') }}" class="btn-secondary mt-4 w-full">Retour</a>
                    @else
                        <div class="mt-6 space-y-2">
                            <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}">
                                @csrf
                                <button type="submit" class="btn-primary w-full">
                                    Accepter l'invitation
                                </button>
                            </form>

                            <form method="POST" action="{{ route('invitations.decline', $invitation->token) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-xl px-4 py-2 text-sm font-semibold text-[#8a95ad] hover:bg-[#f3f6fc]">
                                    Decliner
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </body>
</html>
