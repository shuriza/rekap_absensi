<x-guest-layout>
  <!-- Session Status -->
  <x-auth-session-status class="mb-4" :status="session('status')" />

  <form method="POST" action="{{ route('login') }}">
    @csrf

    <!-- Username -->
    <div>
      <x-input-label for="username" :value="__('Username')" />
      <x-text-input id="username" class="block mt-1 w-full" type="text" name="username"
        :value="old('username')" required autofocus autocomplete="username" />
      <x-input-error :messages="$errors->get('username')" class="mt-2" />
    </div>

    <!-- Password -->
    <div class="mt-4">
      <x-input-label for="password" :value="__('Password')" />
      <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
        required autocomplete="current-password" />
      <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div class="flex items-center justify-end mt-4">
      {{-- <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('register') }}">
        {{ __('Belum punya akun?') }}
      </a> --}}

      <x-primary-button class="ms-4">
        {{ __('Login') }}
      </x-primary-button>
    </div>
  </form>
</x-guest-layout>
