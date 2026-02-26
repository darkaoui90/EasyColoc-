<x-app-layout>
    <x-slot name="header">
        Mes colocations
    </x-slot>

    <div class="space-y-5">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('colocations.create') }}" class="btn-primary">
                + Nouvelle colocation
            </a>
            <a href="{{ route('dashboard') }}" class="btn-secondary">
                Retour dashboard
            </a>
        </div>

        <div class="panel p-6">
            @if ($colocations->isEmpty())
                <div class="flex min-h-[220px] flex-col items-center justify-center rounded-2xl border border-dashed border-[#dbe2ef] bg-[#f9fbff] p-6 text-center">
                    <div class="text-2xl font-extrabold text-[#8090af]">COL</div>
                    <h3 class="mt-3 text-2xl font-bold text-[#3c4963]">Aucune colocation</h3>
                    <p class="mt-1 text-sm text-[#8f9ab1]">Commencez par en creer une nouvelle.</p>
                </div>
            @else
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($colocations as $colocation)
                        <a
                            href="{{ route('colocations.show', $colocation) }}"
                            class="panel-soft p-5 transition hover:-translate-y-0.5 hover:shadow-sm"
                        >
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#7f8aa2]">{{ strtoupper($colocation->status) }}</p>
                            <h3 class="mt-2 text-2xl font-bold">{{ $colocation->name }}</h3>
                            <p class="mt-1 text-sm text-[#7f8aa2]">{{ $colocation->description ?: 'Sans description' }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
