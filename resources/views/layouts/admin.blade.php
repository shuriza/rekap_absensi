<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-100">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DKPP - Admin Dashboard</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="h-full">
  <div class="min-h-full">
    <!-- Navigasi -->
    <nav class="bg-gray-800">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
          <div class="flex items-center">
            <div class="shrink-0">
              <img class="size-8"
                src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500"
                alt="DKPP Logo">
            </div>
            <div class="hidden md:block">
              <div class="ml-10 flex items-baseline space-x-4">
                <a href="{{ route('admin.visual') }}"
                  class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.visual') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Visual</a>
                <a href="{{ route('admin.kelola') }}"
                  class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.kelola') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Kelola</a>
                <a href="{{ route('admin.pelaku_usaha') }}"
                  class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.pelaku_usaha') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Pelaku
                  Usaha</a>
                <a href="{{ route('admin.laporan') }}"
                  class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.laporan') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Laporan</a>
                <a href="{{ route('admin.admin') }}"
                  class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.admin') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Admin</a>
              </div>
            </div>
          </div>
          <div class="hidden md:block">
            <div class="ml-4 flex items-center md:ml-6">
              <!-- Profile dropdown -->
              <div class="relative ml-3">
                <div>
                  <button type="button"
                    class="relative flex max-w-xs items-center rounded-full bg-gray-800 text-sm focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden"
                    id="user-menu-button" aria-expanded="false" aria-haspopup="true"
                    onclick="toggleUserMenu()">
                    <span class="absolute -inset-1.5"></span>
                    <span class="sr-only">Open user menu</span>
                    <img class="size-8 rounded-full"
                      src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                      alt="">
                  </button>
                </div>
                <div
                  class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5 focus:outline-hidden hidden"
                  role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button"
                  tabindex="-1" id="user-menu">
                  <a href="#" class="block px-4 py-2 text-sm text-gray-700" role="menuitem"
                    tabindex="-1">Your Profile</a>
                  <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                      class="block w-full text-left px-4 py-2 text-sm text-gray-700" role="menuitem"
                      tabindex="-1">Sign out</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <div class="-mr-2 flex md:hidden">
            <!-- Mobile menu button -->
            <button type="button"
              class="relative inline-flex items-center justify-center rounded-md bg-gray-800 p-2 text-gray-400 hover:bg-gray-700 hover:text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden"
              aria-controls="mobile-menu" aria-expanded="false" onclick="toggleMobileMenu()">
              <span class="absolute -inset-0.5"></span>
              <span class="sr-only">Open main menu</span>
              <svg class="block size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" aria-hidden="true" data-slot="icon" id="menu-open">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
              </svg>
              <svg class="hidden size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" aria-hidden="true" data-slot="icon" id="menu-close">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile menu -->
      <div class="md:hidden hidden" id="mobile-menu">
        <div class="space-y-1 px-2 pt-2 pb-3 sm:px-3">
          <a href="{{ route('admin.visual') }}"
            class="block rounded-md px-3 py-2 text-base font-medium {{ request()->routeIs('admin.visual') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Visual</a>
          <a href="{{ route('admin.kelola') }}"
            class="block rounded-md px-3 py-2 text-base font-medium {{ request()->routeIs('admin.kelola') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Kelola</a>
          <a href="{{ route('admin.pelaku_usaha') }}"
            class="block rounded-md px-3 py-2 text-base font-medium {{ request()->routeIs('admin.pelaku_usaha') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Pelaku
            Usaha</a>
          <a href="{{ route('admin.laporan') }}"
            class="block rounded-md px-3 py-2 text-base font-medium {{ request()->routeIs('admin.laporan') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Laporan</a>
          <a href="{{ route('admin.admin') }}"
            class="block rounded-md px-3 py-2 text-base font-medium {{ request()->routeIs('admin.admin') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">Admin</a>
        </div>
        <div class="border-t border-gray-700 pt-4 pb-3">
          <div class="flex items-center px-5">
            <div class="shrink-0">
              <img class="size-10 rounded-full"
                src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                alt="">
            </div>
            <div class="ml-3">
              <div class="text-base/5 font-medium text-white">{{ auth()->user()->username }}</div>
              <div class="text-sm font-medium text-gray-400">{{ auth()->user()->email }}</div>
            </div>
          </div>
          <div class="mt-3 space-y-1 px-2">
            <a href="#"
              class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">Your
              Profile</a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit"
                class="block w-full text-left rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">Sign
                out</button>
            </form>
          </div>
        </div>
      </div>
    </nav>

    <!-- Header -->
    <header class="bg-white shadow-sm">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">@yield('title')</h1>
      </div>
    </header>

    <!-- Main Content -->
    <main>
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @yield('content')
      </div>
    </main>
  </div>

  <!-- Script untuk toggle mobile menu dan user menu -->
  <script>
    function toggleMobileMenu() {
      const mobileMenu = document.getElementById('mobile-menu');
      const menuOpen = document.getElementById('menu-open');
      const menuClose = document.getElementById('menu-close');
      mobileMenu.classList.toggle('hidden');
      menuOpen.classList.toggle('hidden');
      menuClose.classList.toggle('hidden');
    }

    function toggleUserMenu() {
      const userMenu = document.getElementById('user-menu');
      userMenu.classList.toggle('hidden');
    }
  </script>
</body>

</html>
