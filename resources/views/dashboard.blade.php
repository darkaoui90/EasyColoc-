<x-app-layout>
    <x-slot name="header">
        Tableau de bord
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('colocations.create') }}" class="btn-primary">
                + Nouvelle colocation
            </a>
            <a href="{{ route('colocations.index') }}" class="btn-secondary">
                Mes colocations
            </a>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="panel p-6">
                <p class="text-sm font-semibold text-[#7f8aa2]">Mon score reputation</p>
                <p class="mt-3 text-5xl font-extrabold leading-none">{{ auth()->user()->reputation }}</p>
            </div>

            <div class="panel p-6">
                <p class="text-sm font-semibold text-[#7f8aa2]">Depenses globales ({{ now()->format('M') }})</p>
                <p class="mt-3 text-5xl font-extrabold leading-none">{{ number_format($monthlyTotal, 2, ',', ' ') }} EUR</p>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1fr_320px]">
            <div class="panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-[#edf1f8] px-5 py-4">
                    <h2 class="text-3xl font-bold">Depenses recentes</h2>
                    @if ($activeColocation)
                        <a href="{{ route('colocations.show', $activeColocation) }}" class="text-sm font-semibold text-[#6f66e8] hover:text-[#4f47e5]">Voir tout</a>
                    @endif
                </div>

                <table class="table-ui w-full">
                    <thead>
                        <tr>
                            <th>Titre / Categorie</th>
                            <th>Payeur</th>
                            <th>Montant</th>
                            <th>Coloc</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentExpenses as $expense)
                            <tr class="border-t border-[#edf1f8]">
                                <td>
                                    <p class="font-semibold">{{ $expense->title }}</p>
                                    <p class="text-xs text-[#8a95ad]">{{ $expense->category?->name ?? 'Sans categorie' }}</p>
                                </td>
                                <td>{{ $expense->payer?->name }}</td>
                                <td class="font-semibold">{{ number_format((float) $expense->amount, 2, ',', ' ') }} EUR</td>
                                <td>{{ $expense->colocation?->name }}</td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#edf1f8]">
                                <td colspan="4" class="py-8 text-center text-[#98a3ba]">Aucune depense recente.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-dark p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold">Membres de la coloc</h3>
                    <span class="rounded-md bg-white/10 px-2 py-1 text-xs font-semibold uppercase">
                        {{ $activeColocation ? 'Actifs' : 'Vide' }}
                    </span>
                </div>

                @if ($activeColocation)
                    <ul class="mt-4 space-y-2">
                        @foreach ($activeColocation->members as $member)
                            <li class="rounded-xl bg-white/5 px-3 py-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span>{{ $member->name }}</span>
                                    <span class="text-[#37e0a0]">{{ $member->reputation }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-[#bdc6e5]">Aucune colocation active.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
