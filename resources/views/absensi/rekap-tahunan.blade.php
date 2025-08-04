{{-- resources/views/absensi/rekap-tahunan.blade.php --}}
@extends('layouts.app')

@section('content')
  <div class="min-h-screen flex flex-col px-6 py-4">
    @php
      // range jam 0–165 dibagi 8 langkah
      $maxHours = 180;
      $minHours = 160; // Minimum ditentukan
      $steps = 8;
      $range = $maxHours - $minHours; // 40 jam
      $stepSize = (int) ceil($range / $steps); // 10 jam per langkah
    @endphp


    {{-- ===============  TAB  =================== --}}
    <div class="my-8 space-x-2">
      <a href="{{ route('absensi.rekap') }}"
        class="px-4 py-2 rounded {{ request()->is('absensi/rekap') ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">
        Rekap Bulanan
      </a>
      <a href="{{ route('absensi.rekap.tahunan') }}"
        class="px-4 py-2 rounded {{ request()->is('absensi/rekap-tahunan') ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">
        Rekap Tahunan
      </a>
    </div>

    <h1 class="text-lg font-semibold mb-4">
      Laporan Tahunan Absensi Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu
    </h1>

    {{-- ===============  FILTER  ================= --}}
    <form method="GET" class="flex flex-wrap items-end gap-4 mb-6">
      {{-- Tahun --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Tahun</label>
        <select name="tahun" class="mt-1 w-28 rounded border-gray-300 text-sm"
          onchange="this.form.submit()">
          @for ($y = 2022; $y <= now()->year; $y++)
            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>
              {{ $y }}</option>
          @endfor
        </select>
      </div>

      {{-- Cari nama --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Cari Nama</label>
        <input name="search" type="text" value="{{ request('search') }}"
          placeholder="Cari nama pegawai…" oninput="this.form.submit()"
          class="mt-1 w-64 rounded border-gray-300 text-sm" />
      </div>

      {{-- Sortir --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Urutkan</label>
        <select name="sort" class="mt-1 w-56 rounded border-gray-300 text-sm"
          onchange="this.form.submit()">
          <option value="" {{ request('sort') == '' ? 'selected' : '' }}>— Tidak diurut —
          </option>
          <option value="nama_asc" {{ request('sort') == 'nama_asc' ? 'selected' : '' }}>Nama A → Z
          </option>
          <option value="nama_desc" {{ request('sort') == 'nama_desc' ? 'selected' : '' }}>Nama Z →
            A</option>
          <option value="total_desc" {{ request('sort') == 'total_desc' ? 'selected' : '' }}>
            Akumulasi ⇣ Terbanyak</option>
          <option value="total_asc" {{ request('sort') == 'total_asc' ? 'selected' : '' }}>Akumulasi
            ⇡ Tersedikit</option>
        </select>
      </div>
    </form>

    {{-- ===========  EXPORT  =========== --}}
    <div class="flex flex-wrap gap-2 mb-4">
      <a href="{{ route('rekap.export.tahunan', ['tahun' => $tahun]) }}"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
        📁 Export Excel Tahunan ({{ $tahun }})
      </a>
    </div>

    {{-- ===========  TABEL  ============= --}}
    <div class="overflow-x-auto border border-gray-300 rounded">
      <table id="tabel-rekap" class="min-w-full text-sm text-center border-collapse display nowrap">
        <thead class="bg-gray-800 text-white">
          <tr>
            <th class="border px-2 py-2">No</th>
            <th class="border px-2 py-2">Nama</th>
            @foreach (range(1, 12) as $bln)
              <th class="border px-2 py-2">
                {{ \Carbon\Carbon::create()->month($bln)->translatedFormat('M') }}
              </th>
            @endforeach
            <th class="border px-2 py-2">Total Akumulasi</th>
          </tr>
        </thead>

        <tbody class="bg-white text-gray-800">
          @foreach ($pegawaiList as $pegawai)
            <tr class="hover:bg-gray-50">
              <td class="border px-2 py-1">{{ $loop->iteration }}</td>
              <td class="border px-2 py-1 text-left">{{ $pegawai->nama }}</td>

              {{-- Jan-Des --}}
              @foreach (range(1, 12) as $bln)
                @php
                  // definisikan mapping yang eksplisit
                  $emeraldShades = [
                    'bg-sky-200 text-black',
                    'bg-sky-300 text-black',
                    'bg-sky-400 text-white',
                    'bg-sky-500 text-white',
                    'bg-sky-600 text-white',
                    'bg-sky-700 text-white',
                    'bg-sky-800 text-white',
                    'bg-sky-900 text-white',
                ];


                  $minutes = $pegawai->menitPerBulan[$bln] ?? 0;
                  $hours = $minutes / 60;
                  $idx = max(0, min((int) floor(($hours - $minHours) / $stepSize), $steps - 1));

                  // ambil literal class dari array
                  $colorClass = $emeraldShades[$idx];
                @endphp

                <td class="border px-2 py-1  {{ $colorClass }}">
                  {{ $pegawai->rekap_tahunan[$bln] ?? '00:00' }}
                </td>
              @endforeach


              {{-- Total setahun : Hari Jam Menit --}}
              <td class="border px-2 py-1 font-semibold">
                {{ $pegawai->total_fmt }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- ===========  DataTables Assets (opsional export)  =========== --}}
  @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet"
      href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
  @endpush

  @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script>
      $(function() {
        $('#tabel-rekap').DataTable({
          paging: false,
          ordering: false,
          searching: false,
          scrollX: true,
        });
      });
    </script>
  @endpush
@endsection
