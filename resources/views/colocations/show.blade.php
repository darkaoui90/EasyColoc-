<x-app-layout>
    <x-slot name="header">
        {{ strtoupper($colocation->name) }}
    </x-slot>

    <div class="space-y-5">
        @if (session('invitation_link'))
            <div class="rounded-xl border border-[#d4ddff] bg-[#f3f5ff] px-4 py-3 text-sm text-[#4f47e5]">
                Lien d'invitation: <a href="{{ session('invitation_link') }}" class="font-semibold underline">{{ session('invitation_link') }}</a>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#77849f]">Status: {{ $colocation->status }}</p>
                @if ($colocation->description)
                    <p class="mt-1 text-sm text-[#7f8aa2]">{{ $colocation->description }}</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($isOwner)
                    <form method="POST" action="{{ route('colocations.cancel', $colocation) }}">
                        @csrf
                        <button type="submit" class="btn-danger" onclick="return confirm('Annuler cette colocation ?')">
                            Annuler la colocation
                        </button>
                    </form>
                @elseif ($canLeave)
                    <form method="POST" action="{{ route('colocations.leave', $colocation) }}">
                        @csrf
                        <button type="submit" class="btn-danger" onclick="return confirm('Quitter cette colocation ?')">
                            Quitter
                        </button>
                    </form>
                @endif

                <a href="{{ route('colocations.index') }}" class="btn-secondary">
                    Retour
                </a>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1fr_340px]">
            <div class="space-y-4">
                <div class="panel overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#edf1f8] px-5 py-4">
                        <h2 class="text-3xl font-bold">Depenses recentes</h2>
                        <details>
                            <summary class="btn-primary cursor-pointer list-none">+ Nouvelle depense</summary>
                            <div class="mt-3 w-[min(100vw-3rem,530px)] rounded-2xl border border-[#e9edf6] bg-[#f8faff] p-4">
                                <form method="POST" action="{{ route('expenses.store', $colocation) }}" class="grid gap-3 md:grid-cols-2">
                                    @csrf
                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-sm font-semibold text-[#4f5d79]">Titre</label>
                                        <input type="text" name="title" required class="input-ui" placeholder="Courses, internet, gaz...">
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-semibold text-[#4f5d79]">Montant</label>
                                        <input type="number" step="0.01" min="0.01" name="amount" required class="input-ui">
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-semibold text-[#4f5d79]">Date</label>
                                        <input type="date" name="date" required value="{{ now()->toDateString() }}" class="input-ui">
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-semibold text-[#4f5d79]">Payeur</label>
                                        <select name="payer_id" class="input-ui">
                                            @foreach ($colocation->members as $member)
                                                <option value="{{ $member->id }}" @selected($member->id === auth()->id())>{{ $member->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-semibold text-[#4f5d79]">Categorie</label>
                                        <select name="category_id" class="input-ui">
                                            <option value="">Sans categorie</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-sm font-semibold text-[#4f5d79]">Nouvelle categorie (optionnelle)</label>
                                        <input type="text" name="new_category" class="input-ui" placeholder="Ex: Transport">
                                    </div>

                                    <div class="md:col-span-2">
                                        <button type="submit" class="btn-primary w-full">Enregistrer la depense</button>
                                    </div>
                                </form>
                            </div>
                        </details>
                    </div>

                    <div class="border-b border-[#edf1f8] px-5 py-4">
                        <form method="GET" action="{{ route('colocations.show', $colocation) }}" class="flex flex-wrap items-center gap-2 text-sm">
                            <label for="month" class="font-semibold text-[#64728f]">Filtrer par mois:</label>
                            <select id="month" name="month" class="input-ui max-w-[180px]" onchange="this.form.submit()">
                                <option value="all" @selected($selectedMonth === 'all')>Tous les mois</option>
                                @foreach ($months as $month)
                                    <option value="{{ $month['month_key'] }}" @selected($selectedMonth === $month['month_key'])>
                                        {{ $month['month_label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <table class="table-ui w-full">
                        <thead>
                            <tr>
                                <th>Titre / Categorie</th>
                                <th>Payeur</th>
                                <th>Montant</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenses as $expense)
                                <tr class="border-t border-[#edf1f8]">
                                    <td>
                                        <p class="font-semibold">{{ $expense->title }}</p>
                                        <p class="text-xs text-[#8a95ad]">{{ $expense->category?->name ?? 'Sans categorie' }}</p>
                                    </td>
                                    <td>{{ $expense->payer?->name }}</td>
                                    <td class="font-semibold">{{ number_format((float) $expense->amount, 2, ',', ' ') }} EUR</td>
                                    <td>
                                        @if ($isOwner || $expense->payer_id === auth()->id())
                                            <form method="POST" action="{{ route('expenses.destroy', [$colocation, $expense]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm font-semibold text-[#d31f51] hover:underline" onclick="return confirm('Supprimer cette depense ?')">
                                                    Supprimer
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-sm text-[#97a3ba]">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-[#edf1f8]">
                                    <td colspan="4" class="py-8 text-center text-[#98a3ba]">Aucune depense pour le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-4">
                <div class="panel p-4">
                    <h3 class="text-2xl font-bold">Qui doit a qui ?</h3>

                    <div class="mt-3 space-y-2">
                        @forelse ($settlements as $settlement)
                            <div class="rounded-xl border border-[#e8edf6] bg-[#f9fbff] p-3">
                                <p class="text-sm">
                                    <span class="font-semibold">{{ $settlement['from_user_name'] }}</span>
                                    doit
                                    <span class="font-semibold">{{ number_format($settlement['amount'], 2, ',', ' ') }} EUR</span>
                                    a
                                    <span class="font-semibold">{{ $settlement['to_user_name'] }}</span>
                                </p>

                                @php($canMarkPaid = auth()->id() === $settlement['from_user_id'] || $isOwner)
                                @if ($canMarkPaid)
                                    <form method="POST" action="{{ route('settlements.store', $colocation) }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="from_user_id" value="{{ $settlement['from_user_id'] }}">
                                        <input type="hidden" name="to_user_id" value="{{ $settlement['to_user_id'] }}">
                                        <input type="hidden" name="amount" value="{{ $settlement['amount'] }}">
                                        <button type="submit" class="btn-primary w-full py-1.5">
                                            Marquer paye
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-xl border border-[#edf1f8] bg-[#fafcff] px-3 py-6 text-center text-sm text-[#98a3ba]">
                                Aucun remboursement en attente.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="panel-dark p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Membres de la coloc</h3>
                        <span class="rounded-md bg-white/10 px-2 py-1 text-xs font-semibold uppercase">Actifs</span>
                    </div>

                    <ul class="mt-3 space-y-2">
                        @foreach ($colocation->members as $member)
                            <li class="rounded-xl bg-white/5 px-3 py-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/10 text-xs font-bold">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-semibold">{{ $member->name }}</p>
                                            <p class="text-[11px] uppercase text-[#f3ba4a]">{{ $member->pivot->role }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold {{ (($balances[$member->id] ?? 0) < 0) ? 'text-[#ff9bb5]' : 'text-[#36dfa0]' }}">
                                            {{ number_format((float) ($balances[$member->id] ?? 0), 2, ',', ' ') }}
                                        </span>

                                        @if ($isOwner && $member->pivot->role !== 'owner' && $member->id !== auth()->id())
                                            <form method="POST" action="{{ route('colocations.members.remove', [$colocation, $member]) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#ff5a7d]/25 text-[10px] font-bold text-[#ff7b99] hover:bg-[#ff5a7d]/35"
                                                    title="Remove member"
                                                    onclick="return confirm('Remove this member from the colocation?')"
                                                >
                                                    x
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @if ($isOwner)
                        <form method="POST" action="{{ route('invitations.store', $colocation) }}" class="mt-4 space-y-2">
                            @csrf
                            <input type="email" name="email" required class="input-ui border-white/10 bg-white/5 text-white placeholder:text-[#98a8d1]" placeholder="email@exemple.com">
                            <button type="submit" class="btn-secondary w-full border-white/15 bg-white/10 text-white hover:bg-white/15">
                                Inviter un membre
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
