@extends('layouts.app')
@section('head')
  <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
  <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Daftar Karyawan</h1>

    {{-- Form Pencarian --}}
    <div class="mb-4">
      <form action="{{ route('absensi.karyawan') }}" method="GET" class="flex items-center gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
          placeholder="Cari nama karyawan..." class="border px-3 py-2 rounded w-1/3" />
        <button type="submit"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Cari</button>
        @if (request('search'))
          <a href="{{ route('absensi.karyawan') }}"
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Reset</a>
        @endif
      </form>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
      <div class="mb-4 p-2 bg-green-200 text-green-700 rounded">
        {{ session('success') }}
      </div>
    @endif

    {{-- Tabel Karyawan --}}
    <table class="w-full border border-gray-300">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 border">Nama</th>
          <th class="p-2 border">Departemen</th>
          <th class="p-2 border">Status</th>
          <th class="p-2 border">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($karyawans as $karyawan)
          <tr data-karyawan-id="{{ $karyawan->id }}">
            <td class="p-2 border">{{ $karyawan->nama }}</td>
            <td class="p-2 border">{{ $karyawan->departemen }}</td>

            {{-- Kolom Status --}}
            <td class="p-2 border text-center">
              @if ($karyawan->sedang_nonaktif)
                <span class="text-red-600 font-semibold text-sm">Nonaktif</span>
              @else
                <span class="text-green-600 font-semibold">Aktif</span>
              @endif
            </td>

            {{-- Tombol Aksi --}}
            <td class="p-2 border">
              @if ($karyawan->sedang_nonaktif)
                <button type="button" onclick="activateEmployee({{ $karyawan->id }})"
                  class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                  Aktifkan
                </button>
              @else
                <button type="button" onclick="deactivateEmployee({{ $karyawan->id }})"
                  class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                  Nonaktifkan
                </button>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-center p-4 text-gray-500">Data tidak ditemukan.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Script --}}
  <script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    async function deactivateEmployee(id) {
      const url = `/karyawan/${id}/nonaktif`;
      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
        });
        const data = await res.json();

        const row = document.querySelector(`tr[data-karyawan-id="${id}"]`);
        if (row) {
          row.querySelector('td:nth-child(3)').innerHTML =
            `<span class="text-red-600 font-semibold text-sm">Nonaktif</span>`;
          row.querySelector('td:nth-child(4)').innerHTML = `
                    <button type="button" onclick="activateEmployee(${id})"
                      class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                      Aktifkan
                    </button>`;
        }
      } catch (err) {
        alert('Gagal menonaktifkan: ' + err.message);
      }
    }

    async function activateEmployee(id) {
      const url = `/karyawan/${id}/aktifkan`;
      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
        });
        const data = await res.json();

        const row = document.querySelector(`tr[data-karyawan-id="${id}"]`);
        if (row) {
          row.querySelector('td:nth-child(3)').innerHTML =
            `<span class="text-green-600 font-semibold">Aktif</span>`;
          row.querySelector('td:nth-child(4)').innerHTML = `
                    <button type="button" onclick="deactivateEmployee(${id})"
                      class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                      Nonaktifkan
                    </button>`;
        }
      } catch (err) {
        alert('Gagal mengaktifkan: ' + err.message);
      }
    }
  </script>
@endsection
