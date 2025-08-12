{{-- resources/views/absensi/rekap-tahunan.blade.php --}}
@extends('layouts.app')

@section('content')
  <div class="min-h-screen flex flex-col px-6 py-4">
    @php
      // === Konfigurasi gradasi kedisiplinan ===
      // Maksimum (kasar) 1 bulan: 7.5 jam x 30 hari = 13.500 menit
      $MAX_MINUTES   = (int) (7.5 * 60 * 30);           // 13.500
      $MAX_BUCKETS   = (int) ceil($MAX_MINUTES / 100);  // skala 100 menit
      $STEPS         = 8;                               // jumlah shade/tingkatan

      // Palet warna (tidak mengubah UI: tetap keluarga "sky-*")
      $skyShades = [
        'bg-sky-200 text-black',
        'bg-sky-300 text-black',
        'bg-sky-400 text-white',
        'bg-sky-500 text-white',
        'bg-sky-600 text-white',
        'bg-sky-700 text-white',
        'bg-sky-800 text-white',
        'bg-sky-900 text-white',
      ];
    @endphp


    {{-- Improved Navigation Tabs --}}
    terakpan gradasi warnya tampa mengubah ui saya
    <div class="my-8">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
          <a href="{{ route('absensi.rekap') }}"
            class="group inline-flex items-center py-2 px-1 border-b-2 font-medium text-sm {{ request()->is('absensi/rekap') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <svg class="w-5 h-5 mr-2 {{ request()->is('absensi/rekap') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Rekap Bulanan
          </a>

          <a href="{{ route('absensi.rekap.tahunan') }}"
            class="group inline-flex items-center py-2 px-1 border-b-2 font-medium text-sm {{ request()->is('absensi/rekap-tahunan') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <svg class="w-5 h-5 mr-2 {{ request()->is('absensi/rekap-tahunan') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
            </svg>
            Rekap Tahunan
          </a>
        </nav>
      </div>
    </div>

    <h1 class="text-lg font-semibold mb-4">
      Laporan Tahunan Absensi Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu
    </h1>

    {{-- Table Container with integrated filters --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      {{-- Table Header with Year Filter --}}
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 0 00-2 2"></path>
            </svg>
            Rekap Tahunan Absensi Karyawan
          </h3>

          <div class="flex items-center space-x-4">
            {{-- Year Filter --}}
            <form method="GET" class="flex items-center space-x-2">
              <label class="text-sm font-medium text-gray-700 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Tahun:
              </label>
              <select name="tahun" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                onchange="this.form.submit()">
                @for ($y = 2022; $y <= now()->year; $y++)
                  <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>
                    {{ $y }}</option>
                @endfor
              </select>
            </form>

            {{-- Info badges --}}
            <div class="flex items-center space-x-2 text-sm text-gray-500">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ $pegawaiList->count() }} Karyawan
              </span>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Tahun {{ $tahun }}
              </span>
            </div>
          </div>
        </div>

        {{-- Export Section --}}
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-600">
            <span class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              Gunakan pencarian dan sorting di tabel untuk filter data
            </span>
          </div>

          <a href="{{ route('rekap.export.tahunan', ['tahun' => $tahun]) }}"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Excel
            <span class="ml-2 text-xs bg-green-500 px-2 py-0.5 rounded-full">
              {{ $tahun }}
            </span>
          </a>
        </div>
      </div>

      {{-- Table Content --}}
      <div class="overflow-x-auto">
        <table id="tabel-rekap" class="min-w-full text-sm text-center border-collapse display nowrap">
          <thead class="bg-gradient-to-r from-gray-100 to-gray-200">
            <tr>
              <th class="border border-gray-300 px-3 py-3 text-gray-800 font-semibold">
                <div class="flex items-center justify-center space-x-1">
                  <span>No</span>
                </div>
              </th>
              <th class="border border-gray-300 px-3 py-3 text-gray-800 font-semibold">
                <div class="flex items-center justify-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  <span>Nama Karyawan</span>
                </div>
              </th>
              @foreach (range(1, 12) as $bln)
                <th class="border border-gray-300 px-3 py-3 text-gray-800 font-medium">
                  <div class="flex items-center justify-center space-x-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>{{ \Carbon\Carbon::create()->month($bln)->translatedFormat('M') }}</span>
                  </div>
                </th>
              @endforeach
              <th class="border border-gray-300 px-3 py-3 text-gray-800 font-semibold">
                <div class="flex items-center justify-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                  </svg>
                  <span>Total Akumulasi</span>
                </div>
              </th>
            </tr>
          </thead>

          <tbody class="bg-white text-gray-800">
            @foreach ($pegawaiList as $pegawai)
              <tr class="hover:bg-gray-50">
                <td class="border px-2 py-1">{{ $loop->iteration }}</td>
                <td class="border px-2 py-1 text-left">{{ $pegawai->nama }}</td>

                {{-- Jan-Des (gradasi: tiap 100 menit makin gelap) --}}
                @foreach (range(1, 12) as $bln)
                  @php
                    $minutes = (int) ($pegawai->menitPerBulan[$bln] ?? 0);
                    $bucket  = (int) floor(max($minutes, 0) / 100);                     // 0..135
                    $ratio   = min($bucket / max($MAX_BUCKETS, 1), 1);                  // 0..1
                    $idx     = (int) floor($ratio * ($STEPS - 1));                       // 0..7
                    $idx     = max(0, min($idx, $STEPS - 1));
                    $colorClass = $skyShades[$idx];
                  @endphp

                  <td class="border px-2 py-1 {{ $colorClass }}">
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

    {{-- Footer --}}
    <footer class="text-center py-4 text-sm text-gray-600">
      Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu &middot;
      Tahun {{ $tahun }}
    </footer>
  </div>

  {{-- DataTables Assets --}}
  @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

    <style>
      .dataTables_wrapper { padding: 1rem; }
      .dataTables_filter { margin-bottom: 1rem; }
      .dataTables_filter input {
        border: 1px solid #d1d5db; border-radius: .375rem;
        padding: .5rem .75rem; margin-left: .5rem; width: 250px;
      }
      .dataTables_filter input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 1px #3b82f6; }
      .dataTables_length select { border: 1px solid #d1d5db; border-radius: .375rem; padding: .25rem .5rem; margin: 0 .5rem; }
      .dataTables_info { color: #6b7280; font-size: .875rem; }
      .dataTables_paginate .paginate_button {
        padding: .5rem .75rem; margin: 0 .125rem; border: 1px solid #d1d5db; border-radius: .375rem; background: #fff; color: #374151;
        text-decoration: none;
      }
      .dataTables_paginate .paginate_button:hover { background: #f3f4f6; border-color: #9ca3af; }
      .dataTables_paginate .paginate_button.current { background: #3b82f6; border-color: #3b82f6; color: #fff; }
      .dataTables_paginate .paginate_button.disabled { color: #9ca3af; cursor: not-allowed; }
      table.dataTable thead th.sorting:after,
      table.dataTable thead th.sorting_asc:after,
      table.dataTable thead th.sorting_desc:after { opacity: .6; }
    </style>
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
          paging: true,
          pageLength: 25,
          lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
          ordering: true,
          searching: true,
          scrollX: true,
          language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" },
            emptyTable: "Tidak ada data yang tersedia",
            zeroRecords: "Tidak ada data yang cocok"
          },
          columnDefs: [
            { targets: [0], orderable: true,  searchable: false },
            { targets: [1], orderable: true,  searchable: true  },
            { targets: [2,3,4,5,6,7,8,9,10,11,12,13], orderable: false, searchable: false },
            { targets: [14], orderable: true, searchable: false, type: 'string' }
          ],
          dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
          initComplete: function () {
            $('.dataTables_filter input').addClass('form-control').attr('placeholder', 'Cari nama karyawan...');
          }
        });
      });
    </script>
  @endpush
@endsection
