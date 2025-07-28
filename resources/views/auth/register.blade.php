<x-guest-layout>
  <x-slot name="logo">
    <x-authentication-card-logo />
  </x-slot>

  <x-validation-errors class="mb-4" />

  <form method="POST" action="{{ route('register') }}">
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
        required autocomplete="new-password" />
      <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <!-- Confirm Password -->
    <div class="mt-4">
      <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
      <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
        name="password_confirmation" required autocomplete="new-password" />
      <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
    </div>

    <div class="flex items-center justify-end mt-4">
      <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
        {{ __('Sudah punya akun?') }}
      </a>

      <x-primary-button class="ms-4">
        {{ __('Register') }}
      </x-primary-button>
    </div>
  </form>
</x-guest-layout>
