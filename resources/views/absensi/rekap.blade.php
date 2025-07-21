<?php /* resources/views/absensi/rekap.blade.php */ ?>

@extends('layouts.app')

@section('content')
  <div class="min-h-screen flex flex-col px-6 py-4 ">

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

    {{-- =============================================
         HEADER & JUDUL
    ============================================= --}}
    <h1 class="text-lg font-semibold mb-4">
      Laporan Detail Absensi Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu
    </h1>

    {{-- =============================================
         FILTER BAR
    ============================================= --}}
    <form method="GET" class="flex flex-wrap items-end gap-4 mb-6">
      {{-- Bulan --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Bulan</label>
        <select name="bulan" class="mt-1 block w-40 rounded border-gray-300 shadow-sm text-sm"
          onchange="this.form.submit()">
          @for ($i = 1; $i <= 12; $i++)
            <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
              {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
            </option>
          @endfor
        </select>
      </div>

      {{-- Tahun --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Tahun</label>
        <select name="tahun" class="mt-1 block w-28 rounded border-gray-300 shadow-sm text-sm"
          onchange="this.form.submit()">
          @for ($y = 2022; $y <= now()->year; $y++)
            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>
              {{ $y }}</option>
          @endfor
        </select>
      </div>

      {{-- Unit Kerja (optional, contoh satu opsi) --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Unit Kerja</label>
        <select name="unit" class="mt-1 block w-80 rounded border-gray-300 shadow-sm text-sm">
          <option value="">-- Semua Unit --</option>
          <option value="DPMPTSP" {{ request('unit') == 'DPMPTSP' ? 'selected' : '' }}>
            Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu
          </option>
        </select>
      </div>

      {{-- Cari Nama (auto submit) --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Cari Nama</label>
        <input type="text" name="search" value="{{ request('search') }}"
          placeholder="Cari nama pegawai..." oninput="this.form.submit()"
          class="mt-1 block w-64 rounded border-gray-300 shadow-sm text-sm" />
      </div>

      {{-- Segment --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Segment Tanggal</label>
        <select name="segment" class="mt-1 block w-44 rounded border-gray-300 shadow-sm text-sm"
          onchange="this.form.submit()">
          <option value="1" {{ request('segment', 1) == 1 ? 'selected' : '' }}>Tanggal 1–10
          </option>
          <option value="2" {{ request('segment') == 2 ? 'selected' : '' }}>Tanggal 11–20
          </option>
          <option value="3" {{ request('segment') == 3 ? 'selected' : '' }}>Tanggal
            21–{{ \Carbon\Carbon::create($tahun, $bulan)->daysInMonth }}</option>
        </select>
      </div>

      {{-- ============= SORTIR ============= --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Urutkan</label>

        <select name="sort"
                class="mt-1 block w-56 rounded border-gray-300 shadow-sm text-sm"
                onchange="this.form.submit()">

          {{-- default: sesuai urutan query (No) --}}
          <option value="" {{ request('sort')=='' ? 'selected' : '' }}>
            — Tidak diurut —
          </option>

          {{-- 🔤 Nama A → Z  --}}
          <option value="nama_asc" {{ request('sort')=='nama_asc' ? 'selected' : '' }}>
            Nama&nbsp;A&nbsp;→&nbsp;Z
          </option>

          {{-- 🔤 Nama Z → A --}}
          <option value="nama_desc" {{ request('sort')=='nama_desc' ? 'selected' : '' }}>
            Nama&nbsp;Z&nbsp;→&nbsp;A
          </option>

          {{-- 🔽 Akumulasi terbanyak --}}
          <option value="total_desc" {{ request('sort')=='total_desc' ? 'selected' : '' }}>
            Akumulasi&nbsp;⇣&nbsp;Terbanyak
          </option>

          {{-- Akumulasi tersedikit --}}
          <option value="total_asc" {{ request('sort')=='total_asc' ? 'selected' : '' }}>
            Akumulasi&nbsp;⇡&nbsp;Tersedikit
          </option>
        </select>
      </div>

      
    </form>
      {{-- =============================================
          FORM ➕ TANDAI TANGGAL MERAH / HARI PENTING
      ============================================= --}}
      @if (session('holiday_success'))
        <div class="mb-4 px-4 py-2 rounded bg-green-100 text-green-800 text-sm">
            {{ session('holiday_success') }}
        </div>
      @endif

      <form action="{{ route('rekap.holiday.add') }}" method="POST"
            class="flex flex-wrap items-end gap-4 mb-6 border p-4 rounded bg-slate-50">
        @csrf

        {{-- Tanggal --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Tanggal</label>
          <input type="date" name="tanggal" required
                class="mt-1 block w-40 rounded border-gray-300 shadow-sm text-sm" />
        </div>

        {{-- Keterangan --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Keterangan</label>
          <input type="text" name="keterangan" required placeholder="Hari Besar / Cuti Bersama ..."
                class="mt-1 block w-72 rounded border-gray-300 shadow-sm text-sm" />
        </div>

        <button type="submit"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
            ➕ Tandai Tanggal
        </button>
      </form>

      {{-- =============================================
          DAFTAR LIBUR BULAN INI  +  Tombol 🗑 Hapus
      ============================================= --}}
      @if ($holidayMap->isNotEmpty())
        <table class="text-xs mb-6 border w-full max-w-md">
          <thead class="bg-slate-200 text-left">
            <tr>
              <th class="p-2">Tanggal</th>
              <th class="p-2">Keterangan</th>
              <th class="p-2 w-8"></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($holidayMap as $h)
              <tr class="border-t">
                <td class="p-2">
                  {{ $h->tanggal->translatedFormat('d F Y') }}
                </td>
                <td class="p-2">{{ $h->keterangan }}</td>
                <td class="p-2 text-right">
                  <form action="{{ route('rekap.holiday.del', $h->id) }}"
                        method="POST"
                        onsubmit="return confirm('Hapus tanggal merah ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="text-white-500 hover:text-red-800 font-semibold"
                            title="Hapus">
                      🗑️
                    </button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif



    {{-- =============================================
         STYLES & SCRIPTS UNTUK DATATABLES EXPORT
    ============================================= --}}
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
         // ===================================================
          //  tombol header manual
          // ===================================================
          $('.sorting').on('click', function () {
              const col = $(this).data('col');   // "nama" / "total"
              if (col === 'nama') {
                  // kolom 1 (index 1) → toggle asc/desc
                  table.order([1, table.order()[0]?.[1]==='asc'?'desc':'asc']).draw();
              }
              if (col === 'total') {
                  // kolom terakhir → index = total kolom - 1
                  const idx = table.columns().count() - 1;
                  table.order([idx, table.order()[0]?.[1]==='desc'?'asc':'desc']).draw();
              }
          });

      </script>
    @endpush

    {{-- =============================================
         TOMBOL EXPORT
    ============================================= --}}
    <div class="flex flex-wrap gap-2 mb-4">
      <a href="{{ route('rekap.export.bulanan', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
        📤 Export Excel Bulanan ({{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }}
        {{ $tahun }})
      </a>
    </div>

    {{-- =============================================
         TABEL REKAP
    ============================================= --}}
    <div class="overflow-x-auto border border-gray-300 rounded">
      <table id="tabel-rekap" class="min-w-full text-sm text-center border-collapse display nowrap">
        <thead class="bg-gray-800 text-white">
          <tr>
            <th class="border px-2 py-2">No</th>
            {{-- <th>NIP</th>  ← dihilangkan --}}
            <th class="border px-2 py-2">Nama</th>
            {{-- <th>Jenjang Jabatan</th>  ← dihilangkan --}}
            @foreach ($tanggalList as $tgl)
                <th class="border px-2 py-2">{{ $tgl }}</th>
            @endforeach
            <th class="border px-2 py-2">Total Akumulasi</th>
          </tr>
        </thead>
        <tbody class="bg-white text-gray-800">

            {{-- import helper Str cukup sekali --}}
            @php
                use Illuminate\Support\Str;
            @endphp

            @foreach ($pegawaiList as $pegawai)
                <tr class="hover:bg-gray-50">
                    <td class="border px-2 py-1">{{ $loop->iteration }}</td>
                    <td class="border px-2 py-1 text-left">{{ $pegawai->nama }}</td>

                    {{-- ------- Kolom tanggal ------- --}}
                    @foreach ($tanggalList as $tgl)
                        @php
                            $sel = $pegawai->absensi_harian[$tgl];

                            /* warna latar  */
                            $bg = match ($sel['type']) {
                                'libur'           => 'bg-gray-300', 
                                'kosong'          => 'bg-red-500',   // merah solid agar kontras
                                'izin'            => 'bg-blue-300',
                                'terlambat'       => 'bg-yellow-200',
                                default           => '',             // hadir normal
                            };

                            /* warna TEKS: putih jika latar merah, hitam jika selainnya */
                            $txt = str_contains($bg, 'bg-red')
                                    ? 'text-white'   // blok merah → teks putih
                                    : 'text-black';  // lainnya → teks hitam
                        @endphp

                        <td class="border px-1 py-1 text-xs text-center {{ $bg }} {{ $txt }}">
                            @switch($sel['type'])
                                @case('hadir')
                                @case('terlambat')
                                    {{ $sel['label'] }}
                                    @break

                                @case('libur')
                                @case('izin')
                                    <span class="inline-block max-w-[140px] truncate"
                                          title="{{ $sel['label'] }}">
                                        {{ \Illuminate\Support\Str::limit($sel['label'], 25, '…') }}
                                    </span>
                                    @break
                                    @case('kosong')                 {{-- hanya in / out / kosong --}}
                                        {{ $sel['label'] }}          {{-- tampilkan “07:12 – --:--” atau “--:-- – 16:10” / “-” --}}
                                        @break

                                @default
                                    -   {{-- kosong --}}
                            @endswitch
                        </td>
                      @endforeach


                    @php
                        $jam   = str_pad(intdiv($pegawai->total_menit, 60), 2, '0', STR_PAD_LEFT);
                        $menit = str_pad($pegawai->total_menit % 60,  2, '0', STR_PAD_LEFT);
                    @endphp
                    <td class="border px-2 py-1 text-xs font-semibold">
                      {{ $pegawai->total_fmt }}
                    </td>
                </tr>
            @endforeach
        </tbody>

      </table>
    </div>

  {{-- =============================================
     FOOTER
============================================= --}}
  <footer class="text-center py-4 text-sm text-gray-600">
    Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu &middot;
    {{ $tahun }} &ndash;
    {{ \Carbon\Carbon::create()->month((int) $bulan)->translatedFormat('F') }}
  </footer>
@endsection
