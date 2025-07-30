<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', config('app.name', 'Laravel'))</title>

  <link rel="icon" href="{{ asset('images/LogoKediri.svg') }}" type="image">
  <!-- Fonts -->

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
    rel="stylesheet" />

  <!-- Scripts -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('styles')
</head>

<body class="font-sans antialiased">
  <div class="min-h-screen bg-gray-100">
    @include('layouts.navigation')

    <!-- Page Heading -->
    @isset($header)
      <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          {{ $header }}
        </div>
      </header>
    @endisset

    <!-- Page Content -->
    <main>
      @yield('content')

      <!-- Modal Ganti Password -->
      @php
        $showModal =
            $errors->has('current_password') || $errors->has('new_password') || session('status');
      @endphp

      <div id="passwordModal"
        class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50 {{ $showModal ? '' : 'hidden' }}">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">

          <h2 class="text-lg font-semibold mb-4">Ganti Password</h2>

          @if (session('status'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded">
              {{ session('status') }}
            </div>
          @endif

          <form method="POST" action="{{ route('password.change') }}">
            @csrf

            <!-- Password Lama -->
            {{-- <div class="mb-4">
              <label for="current_password" class="block text-sm font-medium">Password Lama</label>
              <input type="password" name="current_password" id="current_password"
                class="mt-1 block w-full border rounded px-3 py-2" required>
              @error('current_password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
              @enderror
            </div> --}}

            <!-- Password Baru -->
            <div class="mb-4">
              <label for="new_password" class="block text-sm font-medium">Password Baru</label>
              <input type="password" name="new_password" id="new_password"
                class="mt-1 block w-full border rounded px-3 py-2" required>
              @error('new_password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
              @enderror
            </div>

            <!-- Konfirmasi Password Baru -->
            <div class="mb-4">
              <label for="new_password_confirmation" class="block text-sm font-medium">Konfirmasi
                Password Baru</label>
              <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                class="mt-1 block w-full border rounded px-3 py-2" required>
            </div>

            <div class="flex justify-end gap-2">
              <button type="button"
                onclick="document.getElementById('passwordModal').classList.add('hidden')"
                class="px-4 py-2 bg-gray-300 rounded">
                Batal
              </button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">
                Simpan
              </button>
            </div>
          </form>
        </div>
      </div>

    </main>

  </div>
  @stack('scripts')
</body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</html>
