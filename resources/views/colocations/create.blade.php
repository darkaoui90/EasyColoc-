<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Colocation
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white p-6 rounded shadow">
                <form method="POST" action="{{ route('colocations.store') }}">
                    @csrf

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Name</label>
                        <input name="name" required class="mt-1 block w-full rounded border-gray-300" />
                        @error('name')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button class="px-4 py-2 bg-black text-white rounded">
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>