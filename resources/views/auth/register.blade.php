<x-guest-layout>
    <h1 class="text-3xl font-extrabold text-[#1c2333]">Creer un compte</h1>
    <p class="mt-1 text-sm text-[#7b86a0]">Rejoignez votre colocation en quelques secondes.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="name" class="mb-1 block text-sm font-semibold text-[#4f5d79]">Name</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="input-ui"
            >
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-semibold text-[#4f5d79]">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                class="input-ui"
            >
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-semibold text-[#4f5d79]">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                class="input-ui"
            >
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-semibold text-[#4f5d79]">Confirm Password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="input-ui"
            >
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('login') }}" class="text-sm font-semibold text-[#7a86a0] hover:text-[#4f47e5]">
                Already registered?
            </a>
            <button type="submit" class="btn-primary">
                Creer mon compte
            </button>
        </div>
    </form>
</x-guest-layout>
