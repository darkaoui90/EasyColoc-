<x-guest-layout>
    <h1 class="text-3xl font-extrabold text-[#1c2333]">Connexion</h1>
    <p class="mt-1 text-sm text-[#7b86a0]">Accedez a votre espace EasyColoc.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-semibold text-[#4f5d79]">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
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
                autocomplete="current-password"
                class="input-ui"
            >
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="flex items-center justify-between pt-1">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-[#5f6e8b]">
                <input type="checkbox" name="remember" class="rounded border-[#d7deea] text-[#5048e5]">
                Remember me
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#7a86a0] hover:text-[#4f47e5]">
                    Forgot password?
                </a>
            @endif
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full">
                Se connecter
            </button>
        </div>
    </form>
</x-guest-layout>
