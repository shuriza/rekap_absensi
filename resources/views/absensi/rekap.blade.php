<?php /* resources/views/absensi/rekap.blade.php (UPDATED with modal izin) */ ?>

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


    </form>
    {{-- =============================================
          FORM ➕ TANDAI TANGGAL MERAH / HARI PENTING
      ============================================= --}}
      @php
          // tanggal pertama & terakhir bulan yang sedang difilter
          $firstDay = sprintf('%04d-%02d-01',   $tahun, $bulan);
          $lastDay  = sprintf(
              '%04d-%02d-%02d',
              $tahun,
              $bulan,
              \Carbon\Carbon::create($tahun, $bulan)->daysInMonth
          );
      @endphp
      
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
        <input  type="date"
            name="tanggal"
            required
            value="{{ old('tanggal', $firstDay) }}"   {{-- posisi awal di bulan terpilih --}}
            min="{{ $firstDay }}"                     {{-- tak bisa pilih sebelum bulan ini --}}
            max="{{ $lastDay }}"                      {{-- tak bisa pilih sesudah bulan ini --}}
            class="mt-1 block w-40 rounded border-gray-300 shadow-sm text-sm" />
      </div>

      {{-- Keterangan --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Keterangan</label>
        <input type="text" name="keterangan" required placeholder="Hari Besar / Cuti Bersama ..."
          class="mt-1 block w-72 rounded border-gray-300 shadow-sm text-sm" />
      </div>

      <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
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
                <form action="{{ route('rekap.holiday.del', $h->id) }}" method="POST"
                  onsubmit="return confirm('Hapus tanggal merah ini?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="text-white-500 hover:text-red-800 font-semibold"
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
      <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
     
      <script>
      /* ===========================================================
        1)  Modal Izin – openIzin() tetap seperti semula
      =========================================================== */
      const fpAwal  = flatpickr('#izin-awal',{dateFormat:'Y-m-d'});
      const fpAkhir = flatpickr('#izin-akhir',{dateFormat:'Y-m-d'});

      function openIzin(td){
        const form = document.getElementById('form-izin');

        /* default: mode baru */
        form.action = "{{ route('izin_presensi.store') }}";
        form.querySelector('input[name="_method"]')?.remove();
        document.getElementById('btn-hapus').classList.add('hidden');
        document.getElementById('btn-simpan').textContent='Simpan';

        /* isi field dasar */
        document.getElementById('izin-karyawan').value = td.dataset.karyawan;
        fpAwal.setDate(td.dataset.awal ?? td.dataset.date,true);
        fpAkhir.setDate(td.dataset.akhir?? td.dataset.date,true);

        document.getElementById('tipe-ijin').value  = td.dataset.tipe  || '';
        document.getElementById('jenis-ijin').value = td.dataset.jenis || '';
        document.getElementById('keterangan-izin').value = td.dataset.ket || '';
        document.getElementById('preview-lampiran').innerHTML = td.dataset.file
              ? `<a href="{{ asset('storage') }}/${td.dataset.file}" target="_blank" class="underline">Lampiran sebelumnya</a>`
              : '';

        /* mode edit */
        if(td.dataset.id){
          const m=document.createElement('input');
          m.type='hidden'; m.name='_method'; m.value='PUT';
          form.prepend(m);
          form.action = `/izin_presensi/${td.dataset.id}`;

          document.getElementById('btn-hapus').classList.remove('hidden');
          document.getElementById('btn-hapus').dataset.id = td.dataset.id;
          document.getElementById('btn-simpan').textContent='Perbarui';
        }
        document.getElementById('modal-overlay').classList.remove('hidden');
      }

      function closeIzin(){
        document.getElementById('modal-overlay').classList.add('hidden');
      }

      /* ===========================================================
        2) Modal Konfirmasi Hapus
      =========================================================== */
      let pendingDeleteId = null;

      function showDeleteConfirm(btn){
        /* btn-hapus di form izin memanggil showDeleteConfirm(this) */
        pendingDeleteId = btn.dataset.id;
        openModal('modalConfirm');
      }

      function deleteConfirmed(){
        if(!pendingDeleteId) return;
        const form = document.getElementById('form-izin');

        form.action = `/izin_presensi/${pendingDeleteId}`;
        form.querySelector('input[name="_method"]')?.remove();
        const d=document.createElement('input');
        d.type='hidden'; d.name='_method'; d.value='DELETE';
        form.prepend(d);

        form.submit();
      }

      /* helper open / close modal overlay */
      function openModal(id){
        document.getElementById(id).classList.remove('hidden');
        document.body.classList.add('overflow-y-hidden');
      }
      function closeModal(id){
        document.getElementById(id).classList.add('hidden');
        document.body.classList.remove('overflow-y-hidden');
      }
      document.addEventListener('keydown',e=>{
        if(e.key==='Escape'){
          document.querySelectorAll('.modal').forEach(m=>m.classList.add('hidden'));
          document.body.classList.remove('overflow-y-hidden');
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

    {{-- =========================================================
        TABEL REKAP
    ========================================================= --}}
    <div class="overflow-x-auto border border-gray-300 rounded">
      <table id="tabel-rekap"
            class="min-w-full table-fixed text-sm text-center border-collapse display nowrap">
        <thead class="bg-zinc-400 text-black">
          <tr>
            <th class="border px-2 py-2 cursor-pointer text-black" onclick="resetUrutan()">No</th>
            <th class="border px-2 py-2">Nama</th>

            {{-- kolom tanggal ─────────────── --}}
            @foreach ($tanggalList as $tgl)
              <th class="border px-2 py-2 no-sort">{{ $tgl }}</th>
            @endforeach

            <th class="border px-2 py-2">Total Akumulasi</th>
          </tr>
        </thead>

        <tbody class="bg-white text-gray-800">
          @php  use Illuminate\Support\Str;  @endphp

          @foreach ($pegawaiList as $pegawai)
            <tr class="hover:bg-gray-50">
              {{-- No & Nama --}}
              <td class="border px-2 py-1">{{ $loop->iteration }}</td>
              <td class="border px-2 py-1 text-left">{{ $pegawai->nama }}</td>

              {{-- ───── Kolom tanggal ───── --}}
              @foreach ($tanggalList as $tgl)
                @php
                  $sel = $pegawai->absensi_harian[$tgl]
                        ?? ['type' => 'kosong', 'label' => '-'];

                  /* warna latar */
                  $bg = match ($sel['type']) {
                      'libur'     => 'bg-gray-300',
                      'kosong'    => 'bg-red-500',
                      'izin'      => 'bg-blue-300',
                      'terlambat' => 'bg-yellow-200',
                      default     => '',
                  };

                  /* warna teks */
                  $txt = $bg === 'bg-red-500' ? 'text-white' : 'text-black';
                @endphp

              <td class="border px-1 py-1 text-xs {{ $bg }} {{ $txt }}"
                  data-karyawan="{{ $pegawai->id }}"
                  data-date="{{ sprintf('%04d-%02d-%02d',$tahun,$bulan,$tgl) }}"
                  @if($sel['type']==='izin')
                      data-id="{{ $sel['id'] }}"
                      data-tipe="{{ $sel['tipe'] }}"
                      data-jenis="{{ $sel['jenis'] }}"
                      data-ket="{{ $sel['ket'] }}"
                      data-file="{{ $sel['file'] }}"
                      data-awal="{{ $sel['awal'] }}"
                     data-akhir="{{ $sel['akhir'] }}"
                  @endif
                  onclick="openIzin(this)">
                  @switch($sel['type'])
                      @case('hadir')
                      @case('terlambat')
                          {{ $sel['label'] }}
                          @break

                      @case('libur')
                      @case('izin')
                          <span class="inline-block max-w-[140px] truncate"
                                title="{{ $sel['label'] }}">
                            {{ Str::limit($sel['label'], 25, '…') }}
                          </span>
                          @break

                      @default      {{-- kosong --}}
                          {{ $sel['label'] }}
                  @endswitch
                </td>
              @endforeach

              {{-- ───── Total akumulasi (hari jam menit) + nilai mentah utk sort ───── --}}
              @php
                  $hari  = intdiv($pegawai->total_menit, 1440);
                  $sisa  = $pegawai->total_menit % 1440;
                  $jam   = intdiv($sisa, 60);
                  $menit = $sisa % 60;
                  $tampil = "{$hari}h {$jam}j {$menit}m";
              @endphp
              <td class="border px-2 py-1 text-xs font-semibold">
                  <span class="sr-only">{{ $pegawai->total_menit }}</span>
                  {{ $tampil }}
              </td>
            </tr>
          @endforeach
        </tbody>

      </table>
    </div>

    {{-- =============================================
         MODAL IZIN (overlay)
    ============================================= --}}
    <div id="modal-overlay"
      class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
      <div class="bg-white rounded-xl max-w-2xl w-full p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Input Izin Presensi</h3>
          <button onclick="closeIzin()"
            class="text-xl font-bold text-gray-600 hover:text-red-600">&times;</button>
        </div>
        <form id="form-izin" action="{{ route('izin_presensi.store') }}" method="POST"
          enctype="multipart/form-data" class="space-y-6">
          @csrf
          @include('izin_presensi._form')
        </form>
      </div>
    </div>

    {{-- ========= MODAL KONFIRMASI HAPUS ========= --}}
    <div id="modalConfirm"
        class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-60 modal">
      <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">
        <div class="flex justify-end p-2">
          <button onclick="closeModal('modalConfirm')" type="button"
                  class="text-gray-400 hover:bg-gray-200 rounded-lg p-1.5">
            &times;
          </button>
        </div>

        <div class="p-6 pt-0 text-center">
          <svg class="w-20 h-20 text-red-600 mx-auto" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>

          <h3 class="text-xl font-normal text-gray-500 mt-5 mb-6">
            Yakin ingin menghapus izin ini?
          </h3>

          <button onclick="deleteConfirmed()"
                  class="text-white bg-red-600 hover:bg-red-800 rounded-lg px-3 py-2.5 mr-2">
              Ya, hapus
          </button>

          <button onclick="closeModal('modalConfirm')"
                  class="text-gray-900 bg-white hover:bg-gray-100 border border-gray-200
                        rounded-lg px-3 py-2.5">
            Batal
          </button>
        </div>
      </div>
    </div>
    {{-- ========= END MODAL KONFIRMASI ========= --}}

    {{-- =============================================
         SCRIPT UNTUK DATATABLES

    {{-- =============================================
     FOOTER
============================================= --}}
    <footer class="text-center py-4 text-sm text-gray-600">
      Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu &middot;
      {{ $tahun }} &ndash;
      {{ \Carbon\Carbon::create()->month((int) $bulan)->translatedFormat('F') }}
    </footer>
  </div>
@endsection

