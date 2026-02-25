<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $colocation->name }}
            </h2>
            <span class="text-sm px-2 py-1 rounded bg-gray-100">
                Status: {{ $colocation->status }}
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white p-6 rounded shadow">
                <h3 class="text-lg font-semibold mb-3">Members</h3>

                <ul class="space-y-2">
                    @foreach ($colocation->members as $member)
                        <li class="flex justify-between border-b pb-2">
                            <span>{{ $member->name }}</span>
                            <span class="text-sm text-gray-600">
                                {{ $member->pivot->role }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                @if ($canLeave)
                    <form method="POST" action="{{ route('colocations.leave', $colocation) }}" class="mt-6">
                        @csrf

                        <x-danger-button>
                            Leave Colocation
                        </x-danger-button>
                    </form>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
