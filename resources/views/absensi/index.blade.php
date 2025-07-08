@extends('layouts.app')

@section('content')
  <div class="max-w-4xl mx-auto mt-10 space-y-6">
    {{-- Upload Form --}}
    <div class="bg-white p-6 rounded-xl shadow border">
      <h2 class="text-lg font-semibold mb-2">⏰ Filter Jam Masuk & Pulang</h2>
      <form method="POST" action="{{ route('absensi.preview') }}" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium">Jam Masuk Minimal</label>
            <input type="time" name="jam_masuk_min" value="{{ old('jam_masuk_min', '07:00') }}"
              class="border p-2 rounded w-full">
          </div>
          <div>
            <label class="block text-sm font-medium">Jam Masuk Maksimal</label>
            <input type="time" name="jam_masuk_max" value="{{ old('jam_masuk_max', '07:30') }}"
              class="border p-2 rounded w-full">
          </div>
          <div>
            <label class="block text-sm font-medium">Jam Pulang Minimal</label>
            <input type="time" name="jam_pulang_min" value="{{ old('jam_pulang_min', '15:30') }}"
              class="border p-2 rounded w-full">
          </div>
          <div>
            <label class="block text-sm font-medium">Jam Pulang Maksimal</label>
            <input type="time" name="jam_pulang_max" value="{{ old('jam_pulang_max', '17:00') }}"
              class="border p-2 rounded w-full">
          </div>
        </div>

        <div class="mt-4">
          <input type="file" name="file_excel[]" multiple required
            class="border p-2 rounded w-full mb-4">
          <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Preview
            Data</button>
        </div>
      </form>
    </div>

    {{-- Preview Table --}}
    @if (!empty($preview) && $preview->count())
      <div class="bg-white p-6 rounded-xl shadow border">
        <h2 class="text-xl font-bold mb-4">📄 Preview Data Absensi</h2>
        <p class="text-sm text-gray-600 mb-2">Menampilkan {{ $preview->total() }} data absensi.</p>

        <form method="POST" action="{{ route('absensi.store') }}">
          @csrf
          <table class="w-full text-sm border mb-4">
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
