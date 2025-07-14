@extends('layouts.app')

@section('content')
  <div class="min-h-screen flex flex-col px-6 py-4">
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

    <form method="GET" class="flex flex-wrap items-end gap-4 mb-6">
      <div>
        <label class="block text-sm font-medium text-gray-700">Tahun</label>
        <select name="tahun" class="mt-1 block w-28 rounded border-gray-300 shadow-sm text-sm">
          @for ($y = 2022; $y <= now()->year; $y++)
            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>
              {{ $y }}</option>
          @endfor
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Cari Nama</label>
        <input type="text" name="search" value="{{ request('search') }}"
          placeholder="Cari nama pegawai..." oninput="this.form.submit()"
          class="mt-1 block w-64 rounded border-gray-300 shadow-sm text-sm" />
      </div>
    </form>

    <div class="flex flex-wrap gap-2 mb-4">
      <a href="{{ route('rekap.export.tahunan', ['tahun' => $tahun]) }}"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
        📁 Export Excel Tahunan ({{ $tahun }})
      </a>
    </div>

    <div class="overflow-x-auto border border-gray-300 rounded">
      <table id="tabel-rekap" class="min-w-full text-sm text-center border-collapse display nowrap">
        <thead class="bg-gray-800 text-white">
          <tr>
            <th class="border px-2 py-2">No</th>
            <th class="border px-2 py-2">Nama</th>
            @foreach (range(1, 12) as $bulan)
              <th class="border px-2 py-2">
                {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('M') }}</th>
            @endforeach
            <th class="border px-2 py-2">Total Akumulasi</th>
          </tr>
        </thead>
        <tbody class="bg-white text-gray-800">
          @foreach ($pegawaiList as $i => $pegawai)
            <tr class="hover:bg-gray-50">
              <td class="border px-2 py-1">{{ $i + 1 }}</td>
              <td class="border px-2 py-1 text-left">{{ $pegawai->nama }}</td>
              @php $total = 0; @endphp
              @foreach (range(1, 12) as $bln)
                @php
                  $menit = $pegawai->rekap_tahunan[$bln] ?? 0;
                  $jam = floor($menit / 60);
                  $mnt = $menit % 60;
                  $total += $menit;
                @endphp
                <td class="border px-2 py-1">{{ $jam }}j {{ $mnt }}m</td>
              @endforeach
              <td class="border px-2 py-1 font-semibold">
                @php
                  $jam = floor($total / 60);
                  $mnt = $total % 60;
                @endphp
                {{ $jam }}j {{ $mnt }}m
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

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
          dom: 'Bfrtip',
          buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
          paging: false,
          ordering: false,
          searching: false,
          scrollX: true,
        });
      });
    </script>
  @endpush
@endsection
