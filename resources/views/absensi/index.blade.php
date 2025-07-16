@extends('layouts.app')

@section('content')
  <div class="max-w-4xl mx-auto mt-10 space-y-6">

    {{-- Upload Form --}}
    <div class="bg-white p-6 rounded-xl shadow border">
      <h2 class="text-lg font-semibold mb-2">⏰ Filter Jam Masuk & Pulang</h2>

      {{-- Flash Messages --}}
      @if (session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
          {{ session('success') }}
        </div>
      @endif

      @if (session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
          {{ session('error') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
          <ul class="list-disc ml-5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('absensi.preview') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-6">
          {{-- Senin - Kamis --}}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Jam Masuk Minimal
                (Senin-Kamis)</label>
              <input type="time" name="jam_masuk_min_senin"
                value="{{ old('jam_masuk_min_senin', '07:00') }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Jam Masuk Maksimal
                (Senin-Kamis)</label>
              <input type="time" name="jam_masuk_max_senin"
                value="{{ old('jam_masuk_max_senin', '07:30') }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Jam Pulang Minimal
                (Senin-Kamis)</label>
              <input type="time" name="jam_pulang_min_senin"
                value="{{ old('jam_pulang_min_senin', '15:30') }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Jam Pulang Maksimal
                (Senin-Kamis)</label>
              <input type="time" name="jam_pulang_max_senin"
                value="{{ old('jam_pulang_max_senin', '17:00') }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm">
            </div>
          </div>
          <hr class="border-t-4 border-blue-300 my-4">
          {{-- Jumat --}}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Jam Masuk Minimal (Jumat)</label>
              <input type="time" name="jam_masuk_min_jumat"
                value="{{ old('jam_masuk_min_jumat', '07:00') }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Jam Masuk Maksimal
                (Jumat)</label>
              <input type="time" name="jam_masuk_max_jumat"
                value="{{ old('jam_masuk_max_jumat', '07:30') }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Jam Pulang Minimal
                (Jumat)</label>
              <input type="time" name="jam_pulang_min_jumat"
                value="{{ old('jam_pulang_min_jumat', '15:00') }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Jam Pulang Maksimal
                (Jumat)</label>
              <input type="time" name="jam_pulang_max_jumat"
                value="{{ old('jam_pulang_max_jumat', '17:00') }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm">
            </div>
          </div>
        </div>


        <div class="mt-4">
          <input type="file" name="file_excel[]" multiple required
            class="border p-2 rounded w-full mb-4">
          <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Preview Data
          </button>
        </div>
      </form>
    </div>

    {{-- Preview Table --}}
    @if (!empty($preview) && $preview->count())
      <div class="bg-white p-6 rounded-xl shadow border">
        <h2 class="text-xl font-bold mb-4">📄 Preview Data Absensi</h2>
        <p class="text-sm text-gray-600 mb-2">Menampilkan {{ $preview->total() }} data absensi.</p>

        {{-- Filter Search & Sort --}}
        <form method="GET" action="{{ route('absensi.preview') }}"
          class="mb-4 flex flex-col md:flex-row gap-2 md:items-center justify-between">
          <input type="text" name="search" placeholder="Cari nama..."
            value="{{ request('search') }}" class="border p-2 rounded w-full md:w-1/3" />

          <select name="sort_by" class="border p-2 rounded w-12 md:w-auto">
            <option value="">Urutkan</option>
            <option value="nama_asc" {{ request('sort_by') == 'nama_asc' ? 'selected' : '' }}>Nama
              A-Z</option>
            <option value="nama_desc" {{ request('sort_by') == 'nama_desc' ? 'selected' : '' }}>Nama
              Z-A</option>
            <option value="tanggal_asc" {{ request('sort_by') == 'tanggal_asc' ? 'selected' : '' }}>
              Tanggal Terlama</option>
            <option value="tanggal_desc"
              {{ request('sort_by') == 'tanggal_desc' ? 'selected' : '' }}>Tanggal Terbaru</option>
          </select>

          <button type="submit"
            class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Terapkan</button>
        </form>

        {{-- Form Simpan --}}
        <form method="POST" action="{{ route('absensi.store') }}">
          @csrf
          <table class="w-full text-sm border mb-4 mt-4">
            <thead class="bg-gray-100">
              <tr>
                <th class="border px-2 py-1">Nama</th>
                <th class="border px-2 py-1">Departemen</th>
                <th class="border px-2 py-1">Tanggal</th>
                <th class="border px-2 py-1">Jam Masuk</th>
                <th class="border px-2 py-1">Jam Pulang</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($preview as $i => $row)
                <tr>
                  @foreach (['nama', 'departemen', 'tanggal', 'jam_masuk', 'jam_pulang'] as $key)
                    <td class="border px-2 py-1">
                      <input type="hidden" name="data[{{ $i }}][{{ $key }}]"
                        value="{{ $row[$key] }}">
                      {{ $row[$key] }}
                    </td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>

          <div class="flex items-center justify-between flex-col">
            <button type="submit"
              class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
              Simpan ke Database
            </button>
            <br>
            <div>
              {{ $preview->links() }}
            </div>
          </div>
        </form>
      </div>
    @elseif(isset($preview))
      <div class="bg-white p-6 rounded-xl shadow border text-gray-500 italic">
        Tidak ada data absensi yang bisa ditampilkan.
      </div>
    @endif
  </div>
@endsection
