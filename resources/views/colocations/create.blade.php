<x-app-layout>
    <x-slot name="header">
        Nouvelle colocation
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="panel p-7">
            <form method="POST" action="{{ route('colocations.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="mb-1 block text-sm font-semibold text-[#4f5d79]">Nom de la colocation</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="input-ui"
                        placeholder="ex: Residence Les Lilas"
                    >
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <label for="description" class="mb-1 block text-sm font-semibold text-[#4f5d79]">Description (optionnelle)</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="input-ui"
                        placeholder="Decrivez brievement votre colocation..."
                    >{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary">
                        Creer la colocation
                    </button>
                    <a href="{{ route('colocations.index') }}" class="text-sm font-semibold text-[#7e8aa2] hover:text-[#4f47e5]">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
