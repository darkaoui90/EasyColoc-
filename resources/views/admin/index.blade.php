<x-app-layout>
    <x-slot name="header">
        Supervision globale
    </x-slot>

    <div class="space-y-5">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="panel p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#8b97ae]">Utilisateurs</p>
                <p class="mt-2 text-5xl font-extrabold">{{ $stats['users_total'] }}</p>
                <p class="mt-1 text-xs font-semibold text-[#2fb884]">Total</p>
            </div>
            <div class="panel p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#8b97ae]">Colocations</p>
                <p class="mt-2 text-5xl font-extrabold">{{ $stats['colocations_active'] }}</p>
                <p class="mt-1 text-xs font-semibold text-[#6c74eb]">Actives</p>
            </div>
            <div class="panel p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#8b97ae]">Depenses</p>
                <p class="mt-2 text-5xl font-extrabold">{{ number_format($stats['expenses_total'], 2, ',', ' ') }} EUR</p>
                <p class="mt-1 text-xs font-semibold text-[#6c74eb]">Total cumule</p>
            </div>
            <div class="panel p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#8b97ae]">Bannis</p>
                <p class="mt-2 text-5xl font-extrabold text-[#db2e5c]">{{ $stats['banned_total'] }}</p>
                <p class="mt-1 text-xs font-semibold text-[#db2e5c]">A surveiller</p>
            </div>
        </div>

        <div class="panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#edf1f8] px-5 py-4">
                <h2 class="text-3xl font-bold">Gestion des utilisateurs</h2>

                <form method="GET" action="{{ route('admin.index') }}" class="flex items-center gap-2">
                    <input type="text" name="q" value="{{ $search }}" class="input-ui w-64" placeholder="Rechercher email / nom...">
                    <button type="submit" class="btn-secondary">Rechercher</button>
                </form>
            </div>

            <table class="table-ui w-full">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Reputation</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-t border-[#edf1f8]">
                            <td>#{{ $user->id }}</td>
                            <td class="font-semibold">{{ $user->name }}</td>
                            <td class="text-[#6f7c95]">{{ $user->email }}</td>
                            <td class="font-semibold {{ $user->reputation < 0 ? 'text-[#d52f5e]' : 'text-[#1ca979]' }}">
                                {{ $user->reputation }} pts
                            </td>
                            <td>
                                @if ($user->is_banned)
                                    <span class="rounded-full bg-[#fff0f5] px-2 py-1 text-xs font-semibold text-[#d42759]">Banni</span>
                                @else
                                    <span class="rounded-full bg-[#ecfff7] px-2 py-1 text-xs font-semibold text-[#199767]">Actif</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->role === 'admin')
                                    <span class="text-xs font-bold uppercase tracking-wide text-[#7f8aa2]">Protege</span>
                                @else
                                    <form method="POST" action="{{ route('admin.users.toggle-ban', $user) }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-semibold {{ $user->is_banned ? 'text-[#1b9a69]' : 'text-[#d32758]' }}">
                                            {{ $user->is_banned ? 'Debannir' : 'Bannir' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-[#edf1f8]">
                            <td colspan="6" class="py-8 text-center text-[#98a3ba]">Aucun utilisateur trouve.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-t border-[#edf1f8] px-5 py-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
