<?php /* resources/views/absensi/rekap.blade.php (UPDATED with modal izin) */ ?>

@extends('layouts.app')

@section('content')
  <div class="min-h-screen flex flex-col px-6 py-4 ">

    {{-- Improved Navigation Tabs --}}
    <div class="my-8">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
          <a href="{{ route('dashboard.analytics') }}"
            class="group inline-flex items-center py-2 px-1 border-b-2 font-medium text-sm {{ request()->is('dashboard') ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <svg class="w-5 h-5 mr-2 {{ request()->is('dashboard') ? 'text-purple-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Dashboard Analytics
              </a>
          
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

    {{-- =============================================
         HEADER & JUDUL
    ============================================= --}}
    <h1 class="text-lg font-semibold mb-4">
      Laporan Detail Absensi Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu
    </h1>

    {{-- =============================================
         FILTER BAR WITH MODERN CARD DESIGN
    ============================================= --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 flex items-center">
          <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
          </svg>
          Filter Data
        </h3>
        <span class="text-sm text-gray-500">Pilih periode dan kriteria filter</span>
      </div>
      
      <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Bulan --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Bulan
          </label>
          <select name="bulan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors"
            onchange="this.form.submit()">
            @for ($i = 1; $i <= 12; $i++)
              <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
              </option>
            @endfor
          </select>
        </div>

        {{-- Tahun --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Tahun
          </label>
          <select name="tahun" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors"
            onchange="this.form.submit()">
            @for ($y = 2022; $y <= now()->year; $y++)
              <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
          </select>
        </div>

        {{-- Cari Nama --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Cari Nama
          </label>
          <div class="relative">
            <input type="text" name="search" value="{{ request('search') }}"
              placeholder="Ketik nama pegawai..." oninput="this.form.submit()"
              class="w-full pl-10 pr-4 py-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors" />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            @if(request('search'))
              <button type="button" onclick="document.querySelector('input[name=search]').value=''; this.closest('form').submit();" 
                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            @endif
          </div>
        </div>

        {{-- Segment --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
            </svg>
            Segment Tanggal
          </label>
          <select name="segment" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors"
            onchange="this.form.submit()">
            <option value="1" {{ request('segment', 1) == 1 ? 'selected' : '' }}>📅 Tanggal 1–10</option>
            <option value="2" {{ request('segment') == 2 ? 'selected' : '' }}>📅 Tanggal 11–20</option>
            <option value="3" {{ request('segment') == 3 ? 'selected' : '' }}>📅 Tanggal 21–{{ \Carbon\Carbon::create($tahun, $bulan)->daysInMonth }}</option>
          </select>
        </div>
      </form>

      {{-- Active Filters Indicator --}}
      @if(request('search') || request('segment', 1) != 1)
        <div class="mt-4 pt-4 border-t border-gray-200">
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">Filter aktif:</span>
            @if(request('search'))
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Nama: "{{ request('search') }}"
                <button type="button" onclick="document.querySelector('input[name=search]').value=''; document.querySelector('form').submit();" class="ml-1 hover:text-blue-600">×</button>
              </span>
            @endif
            @if(request('segment', 1) != 1)
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Segment: {{ request('segment') == 2 ? '11-20' : '21-31' }}
              </span>
            @endif
          </div>
        </div>
      @endif
    </div>
    {{-- =============================================
          FORM ➕ TANDAI TANGGAL MERAH / HARI PENTING
      ============================================= --}}
    @php
      // tanggal pertama & terakhir bulan yang sedang difilter
      $firstDay = sprintf('%04d-%02d-01', $tahun, $bulan);
      $lastDay = sprintf(
          '%04d-%02d-%02d',
          $tahun,
          $bulan,
          \Carbon\Carbon::create($tahun, $bulan)->daysInMonth,
      );
    @endphp

    {{-- =============================================
         HOLIDAY SUCCESS NOTIFICATION
    ============================================= --}}
    @if (session('holiday_success'))
      <div class="mb-6 relative">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-400 p-4 rounded-lg shadow-sm">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-blue-800">
                📅 {{ session('holiday_success') }}
              </p>
            </div>
            <div class="ml-auto pl-3">
              <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                class="inline-flex text-blue-400 hover:text-blue-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    @endif

    <div class="flex flex-wrap justify-between items-start mb-6 border p-4 rounded bg-slate-50">

      {{-- ======= Form Tandai Tanggal (kiri) ======= --}}
      <form action="{{ route('rekap.holiday.add') }}" method="POST"
        class="flex flex-wrap items-end gap-4">
        @csrf

        {{-- Tanggal --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Tanggal</label>
          <input type="date" name="tanggal" required value="{{ old('tanggal', $firstDay) }}"
            min="{{ $firstDay }}" max="{{ $lastDay }}"
            class="mt-1 block w-40 rounded border-gray-300 shadow-sm text-sm" />
        </div>

        {{-- Keterangan --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Keterangan</label>
          <input type="text" name="keterangan" required
            placeholder="Hari Besar / Cuti Bersama ..."
            class="mt-1 block w-72 rounded border-gray-300 shadow-sm text-sm" />
        </div>

        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
          ➕ Tandai Tanggal
        </button>
      </form>

      

    </div>


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
      
      {{-- Custom Tooltip Styles --}}
      <style>
        .custom-tooltip {
          position: absolute;
          background: #1f2937;
          color: white;
          padding: 12px 16px;
          border-radius: 8px;
          font-size: 13px;
          line-height: 1.5;
          max-width: 400px;
          min-width: 200px;
          white-space: pre-line;
          z-index: 1000;
          box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
          opacity: 0;
          transition: opacity 0.3s ease-in-out;
          pointer-events: none;
          word-wrap: break-word;
          border: 1px solid #374151;
        }
        
        .custom-tooltip.show {
          opacity: 1;
        }
        
        .custom-tooltip::before {
          content: '';
          position: absolute;
          left: 50%;
          transform: translateX(-50%);
          width: 0;
          height: 0;
          border-left: 6px solid transparent;
          border-right: 6px solid transparent;
        }
        
        .custom-tooltip:not([style*="--arrow-position"])::before {
          top: -6px;
          border-bottom: 6px solid #1f2937;
        }
        
        .custom-tooltip[style*="--arrow-position: top"]::before {
          bottom: -6px;
          border-top: 6px solid #1f2937;
        }
        
        /* Hover effect untuk sel yang memiliki tooltip */
        td[data-tooltip]:hover {
          position: relative;
          transition: all 0.2s ease;
        }
        
        /* Style khusus untuk kolom izin */
        td[data-tooltip][data-id] {
          cursor: pointer;
        }
        
        td[data-tooltip][data-id]:hover {
          opacity: 0.9;
          transform: scale(1.02);
        }
        
        /* Responsive tooltip untuk mobile */
        @media (max-width: 768px) {
          .custom-tooltip {
            max-width: 280px;
            font-size: 12px;
            padding: 10px 12px;
          }
        }
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
      <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

      <script>
        /* ===========================================================
                    1)  Modal Izin – openIzin() tetap seperti semula
                  =========================================================== */
        const fpAwal = flatpickr('#izin-awal', {
          dateFormat: 'Y-m-d'
        });
        const fpAkhir = flatpickr('#izin-akhir', {
          dateFormat: 'Y-m-d'
        });

        /* base URL ke route lampiran */
        const lampiranBase = "{{ url('/izin-presensi') }}";

        /* ===========================================================
            Custom Tooltip Implementation
        =========================================================== */
        let currentTooltip = null;

        function createTooltip(element, text) {
          // Hapus tooltip yang ada
          removeTooltip();
          
          if (!text || text.trim() === '') return;
          
          const tooltip = document.createElement('div');
          tooltip.className = 'custom-tooltip';
          tooltip.textContent = text;
          
          document.body.appendChild(tooltip);
          currentTooltip = tooltip;
          
          // Posisi tooltip dengan perhitungan yang lebih baik
          const rect = element.getBoundingClientRect();
          const tooltipRect = tooltip.getBoundingClientRect();
          const scrollX = window.pageXOffset || document.documentElement.scrollLeft;
          const scrollY = window.pageYOffset || document.documentElement.scrollTop;
          
          let left = rect.left + scrollX + (rect.width / 2) - (tooltipRect.width / 2);
          let top = rect.top + scrollY - tooltipRect.height - 10;
          
          // Pastikan tooltip tidak keluar dari viewport (horizontal)
          if (left < 10) {
            left = 10;
          } else if (left + tooltipRect.width > window.innerWidth - 10) {
            left = window.innerWidth - tooltipRect.width - 10;
          }
          
          // Pastikan tooltip tidak keluar dari viewport (vertical)
          if (top < scrollY + 10) {
            // Tampilkan di bawah jika tidak muat di atas
            top = rect.bottom + scrollY + 10;
            tooltip.style.setProperty('--arrow-position', 'top');
          }
          
          tooltip.style.left = left + 'px';
          tooltip.style.top = top + 'px';
          
          // Show tooltip dengan delay untuk smooth animation
          requestAnimationFrame(() => {
            tooltip.classList.add('show');
          });
        }

        function removeTooltip() {
          if (currentTooltip) {
            currentTooltip.remove();
            currentTooltip = null;
          }
        }

        // Event listeners untuk tooltip
        document.addEventListener('DOMContentLoaded', function() {
          const tooltipElements = document.querySelectorAll('[data-tooltip]');
          let tooltipTimeout;
          
          tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', function(e) {
              const tooltipText = this.getAttribute('data-tooltip');
              // Hanya tampilkan tooltip untuk izin, libur, atau informasi yang berguna
              const tipe = this.getAttribute('data-id') ? 'izin' : 
                          this.classList.contains('bg-gray-300') ? 'libur' : 'lain';
              
              if (tooltipText && tooltipText.trim() !== '' && tooltipText !== '-' && 
                  (tipe === 'izin' || tipe === 'libur' || 
                   (tooltipText.includes('Jenis:') || tooltipText.includes('Hari Libur:')))) {
                
                // Clear any existing timeout
                clearTimeout(tooltipTimeout);
                
                // Small delay untuk smooth UX
                tooltipTimeout = setTimeout(() => {
                  createTooltip(this, tooltipText);
                }, 100);
              }
            });
            
            element.addEventListener('mouseleave', function() {
              // Clear timeout jika mouse keluar sebelum tooltip muncul
              clearTimeout(tooltipTimeout);
              
              // Delay sedikit sebelum menghilangkan tooltip agar user bisa baca
              setTimeout(() => {
                removeTooltip();
              }, 150);
            });
          });
        });

        // Hapus tooltip saat scroll atau resize
        window.addEventListener('scroll', removeTooltip);
        window.addEventListener('resize', removeTooltip);

        function showIzinAlert(msg) {
          const alertBox = document.getElementById('alert-izin');
          const alertMsg = document.getElementById('alert-izin-msg');
          alertMsg.textContent = msg;
          alertBox.classList.remove('hidden');
          setTimeout(() => {
            alertBox.classList.add('hidden');
          }, 2500);
        }

        function openIzin(td) {
          // Cek jika kolom adalah hari Sabtu/Minggu atau cuti/libur
          const tgl = td.dataset.date;
          const tipe = td.dataset.tipe || td.dataset.type || '';
          const label = td.textContent?.trim() || '';
          // Cek cuti/libur dari tipe
          if (tipe === 'libur' || tipe === 'cuti') {
            showIzinAlert('Tidak bisa input izin pada hari libur/cuti.');
            return;
          }
          // Cek Sabtu/Minggu dari tanggal (jika format YYYY-MM-DD)
          if (tgl) {
            const d = new Date(tgl);
            const day = d.getDay(); // 0 = Minggu, 6 = Sabtu
            if (day === 0 || day === 6) {
              showIzinAlert('Tidak bisa input izin pada hari Sabtu/Minggu.');
              return;
            }
          }
          // Cek kolom tanggal merah (fitur tandai tanggal)
          if (td.classList.contains('bg-gray-300')) {
            showIzinAlert('Tidak bisa input izin pada tanggal merah.');
            return;
          }
          // Cek kolom yang ada isinya jam (misal: 07:30, 08:00, dst)
          if (/\d{1,2}:\d{2}/.test(label)) {
            showIzinAlert('Tidak bisa input izin pada kolom yang sudah ada jam hadir.');
            return;
          }

          const form = document.getElementById('form-izin');

          /* default: mode baru */
          form.action = "{{ route('izin_presensi.store') }}";
          form.querySelector('input[name="_method"]')?.remove();
          document.getElementById('btn-hapus').classList.add('hidden');
          document.getElementById('btn-simpan').textContent = 'Simpan';

          /* isi field dasar */
          document.getElementById('izin-karyawan').value = td.dataset.karyawan;
          fpAwal.setDate(td.dataset.awal ?? td.dataset.date, true);
          fpAkhir.setDate(td.dataset.akhir ?? td.dataset.date, true);

          document.getElementById('tipe-ijin').value    = td.dataset.tipe  || '';
          document.getElementById('jenis-ijin').value   = td.dataset.jenis || '';
          document.getElementById('keterangan-izin').value = td.dataset.ket || '';

          /* ======================== perubahan utama ======================== */
          document.getElementById('preview-lampiran').innerHTML =
            td.dataset.id && td.dataset.file
              ? `<a href="${lampiranBase}/${td.dataset.id}/lampiran"
                    target="_blank"
                    class="underline">
                  Lampiran sebelumnya
                </a>`
              : '';
          /* ================================================================= */

          /* mode edit */
          if (td.dataset.id) {
            const m = document.createElement('input');
            m.type  = 'hidden';
            m.name  = '_method';
            m.value = 'PUT';
            form.prepend(m);
            form.action = `/izin_presensi/${td.dataset.id}`;

            document.getElementById('btn-hapus').classList.remove('hidden');
            document.getElementById('btn-hapus').dataset.id = td.dataset.id;
            document.getElementById('btn-simpan').textContent = 'Perbarui';
          }
          document.getElementById('modal-overlay').classList.remove('hidden');
        }

        function closeIzin() {
          document.getElementById('modal-overlay').classList.add('hidden');
        }

        /* ===========================================================
            2) Modal Konfirmasi Hapus
        =========================================================== */
        let pendingDeleteId = null;

        function showDeleteConfirm(btn) {
          /* btn-hapus di form izin memanggil showDeleteConfirm(this) */
          pendingDeleteId = btn.dataset.id;
          openModal('modalConfirm');
        }

        function deleteConfirmed() {
          if (!pendingDeleteId) return;
          const form = document.getElementById('form-izin');

          form.action = `/izin_presensi/${pendingDeleteId}`;
          form.querySelector('input[name="_method"]')?.remove();
          const d = document.createElement('input');
          d.type  = 'hidden';
          d.name  = '_method';
          d.value = 'DELETE';
          form.prepend(d);

          form.submit();
        }

        /* helper open / close modal overlay */
        function openModal(id) {
          document.getElementById(id).classList.remove('hidden');
          document.body.classList.add('overflow-y-hidden');
        }

        function closeModal(id) {
          document.getElementById(id).classList.add('hidden');
          document.body.classList.remove('overflow-y-hidden');
        }

        document.addEventListener('keydown', e => {
          if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(m => m.classList.add('hidden'));
            document.body.classList.remove('overflow-y-hidden');
          }
        });
      </script>

      <script>
        let dt;

        $(function() {
          const jumlahTanggal = Number("{{ count($tanggalList) }}");
          const kolomTanggal = Array.from({
            length: jumlahTanggal
          }, (_, i) => i + 2);

          dt = $('#tabel-rekap').DataTable({
            paging: false,
            searching: false,
            scrollX: true,
            ordering: true,
            order: [],

            // Konfigurasi kolom
            columns: [{
                data: null,
                title: "No",
                render: (data, type, row, meta) => meta.row + 1
              }, // ✅ kolom No dinamis & sortable
              null, // Nama
              ...kolomTanggal.map(() => null), // Tanggal
              null // Total akumulasi
            ],

            columnDefs: [{
                targets: kolomTanggal,
                orderable: false
              },
              {
                targets: 'no-sort',
                orderable: false
              }
            ],
          });
        });

        // ✅ Tombol Reset
        function resetUrutan() {
          dt.order([]).draw();
        }
      </script>

      <script>
        function openObModal() {
          document.getElementById('modalOb').classList.remove('hidden');
          document.body.classList.add('overflow-y-hidden');
        }

        // Pastikan closeModal sudah ada di script Anda
        function closeModal(id) {
          document.getElementById(id).classList.add('hidden');
          document.body.classList.remove('overflow-y-hidden');
        }
      </script>
    @endpush

    {{-- =============================================
         EXPORT CONTROLS WITH MODERN DESIGN
    ============================================= --}}
    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg border border-green-200 p-6 mb-6">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
        
        {{-- Export Header --}}
        <div class="flex items-center space-x-3">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Export & Actions</h3>
            <p class="text-sm text-gray-600">Download laporan dan kelola data karyawan</p>
          </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap gap-3">
          {{-- Excel Export --}}
          <a href="{{ route('rekap.export.bulanan', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M7 13h10M7 13V8a2 2 0 012-2h6a2 2 0 012 2v5M7 13v5a2 2 0 002 2h6a2 2 0 002-2v-5"></path>
            </svg>
            📤 Export Excel
            <span class="ml-2 text-xs bg-green-500 px-2 py-0.5 rounded-full">
              {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('M') }} {{ $tahun }}
            </span>
          </a>

          {{-- OB Management --}}
          <button onclick="openObModal()" 
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            📋 Kelola OB
          </button>
        </div>
      </div>
    </div>

    

    {{-- =============================================
        MODAL OB MANAGEMENT - SIMPLIFIED
    ============================================= --}}
    <div id="modalOb" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-60 modal">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
          {{-- Modal Header --}}
          <div class="flex items-center justify-between pb-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Kelola Status OB</h3>
            <button onclick="closeModal('modalOb')" type="button"
              class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          {{-- Modal Content --}}
          <form id="form-ob" action="{{ route('update-ob-batch') }}" method="POST" class="mt-6">
            @csrf
            
            {{-- Employee List --}}
            <div id="ob-list" class="max-h-80 overflow-y-auto border border-gray-200 rounded-lg">
              @foreach ($pegawaiList as $pegawai)
                <div class="flex items-center justify-between p-3 hover:bg-blue-50 border-b border-gray-100 last:border-b-0">
                  <div class="flex items-center space-x-3">
                    @php
                      $inputName = 'ob_ids[]';
                      $isChecked = $pegawai->is_ob;
                    @endphp
                    <input type="checkbox" 
                      name="{{ $inputName }}" 
                      value="{{ $pegawai->id }}" 
                      @if($isChecked) checked @endif
                      class="w-4 h-4 text-blue-600 rounded">
                    <div>
                      <div class="font-medium text-gray-900">{{ $pegawai->nama }}</div>
                      <div class="text-sm text-gray-600">{{ $pegawai->departemen }}</div>
                    </div>
                  </div>
                  @if($pegawai->is_ob)
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Active OB</span>
                  @endif
                </div>
              @endforeach
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
              <button type="button" onclick="closeModal('modalOb')" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Batal
              </button>
              <button type="submit" 
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                💾 Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    {{-- =============================================
         MODERN NOTIFICATION MESSAGES
    ============================================= --}}
    @if (session('ob_success'))
      <div class="mb-6 relative">
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-400 p-4 rounded-lg shadow-sm">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800">
                ✅ {{ session('ob_success') }}
              </p>
            </div>
            <div class="ml-auto pl-3">
              <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                class="inline-flex text-green-400 hover:text-green-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    @endif

    {{-- ========================================================
        MODERN TABLE CONTAINER
    ========================================================= --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      {{-- Table Header --}}
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
            </svg>
            Rekap Absensi Karyawan
          </h3>

           <div class="flex flex-row gap-2 mt-2 text-sm">
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-red-500 rounded border"></div>
              <span>Kosong</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-yellow-200 rounded border"></div>
              <span>Terlambat</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-blue-300 rounded border"></div>
              <span>Izin</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-gray-300 rounded border"></div>
              <span>Hari Libur</span>
            </div>
          </div>
          <div class="flex items-center space-x-2 text-sm text-gray-500">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              {{ $pegawaiList->count() }} Karyawan
            </span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
              {{ \Carbon\Carbon::create($tahun, $bulan)->translatedFormat('F Y') }}
            </span>
          </div>
        </div>
      </div>

      {{-- Table Content --}}
      <div class="overflow-x-auto">
        <table id="tabel-rekap"
          class="min-w-full table-fixed text-sm text-center border-collapse display nowrap">
          <thead class="bg-gradient-to-r from-gray-100 to-gray-200">
            <tr>
              <th class="border border-gray-300 px-3 py-3 cursor-pointer text-gray-800 font-semibold hover:bg-gray-200 transition-colors" 
                onclick="resetUrutan()" title="Klik untuk reset urutan">
                <div class="flex items-center justify-center space-x-1">
                  <span>No</span>
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
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

            {{-- kolom tanggal ─────────────── --}}
            @foreach ($tanggalList as $tgl)
              <th class="border border-gray-300 w-[70px] px-2 py-3 no-sort text-gray-800 font-medium">{{ $tgl }}</th>
            @endforeach

            <th class="border border-gray-300 px-3 py-3 text-gray-800 font-semibold">Total Akumulasi</th>
          </tr>
        </thead>

        <tbody class="bg-white text-gray-800">
          @php  use Illuminate\Support\Str;  @endphp

          @foreach ($pegawaiList as $pegawai)
            <tr class="hover:bg-gray-50">
              {{-- No & Nama --}}
              <td class="border px-2 py-1"></td>
              <td class="border px-2 py-1 text-left">{{ $pegawai->nama }}</td>

              {{-- ───── Kolom tanggal ───── --}}
              @foreach ($tanggalList as $tgl)
                @php
                  $sel = $pegawai->absensi_harian[$tgl] ?? ['type' => 'kosong', 'label' => '-'];

                  /* warna latar */
                  $bg = match ($sel['type']) {
                      'libur' => 'bg-gray-300',
                      'kosong' => 'bg-red-500',
                      'izin' => 'bg-blue-300',
                      'terlambat' => 'bg-yellow-200',
                      default => '',
                  };

                  /* warna teks */
                  $txt = $bg === 'bg-red-500' ? 'text-white' : 'text-black';
                @endphp

                @php
                  // Buat tooltip lengkap untuk izin
                  $tooltipText = '';
                  if ($sel['type'] === 'izin') {
                    // Cari jenis lengkap dari array jenisLengkap berdasarkan jenis yang tersimpan
                    $jenisLengkap = $jenisLengkap ?? [];
                    $jenisAsli = $sel['jenis'] ?? 'Tidak ada jenis';
                    
                    // Coba cari jenis lengkap yang cocok
                    $jenisTooltip = $jenisAsli;
                    foreach ($jenisLengkap as $lengkap) {
                      if (str_starts_with($lengkap, $jenisAsli) || 
                          str_contains($lengkap, explode(' ', $jenisAsli)[0])) {
                        $jenisTooltip = $lengkap;
                        break;
                      }
                    }
                    
                    // Mulai dengan jenis izin lengkap
                    $tooltipText = "Jenis: " . $jenisTooltip;
                    
                    // Tambahkan keterangan jika ada
                    if (!empty($sel['ket']) && $sel['ket'] !== '-') {
                      $tooltipText .= "\n\nKeterangan: " . $sel['ket'];
                    }
                    
                    // Tambahkan periode jika berbeda dari tanggal tunggal
                    if (!empty($sel['awal']) && !empty($sel['akhir'])) {
                      if ($sel['awal'] !== $sel['akhir']) {
                        $awalFmt = \Carbon\Carbon::parse($sel['awal'])->translatedFormat('d M Y');
                        $akhirFmt = \Carbon\Carbon::parse($sel['akhir'])->translatedFormat('d M Y');
                        $tooltipText .= "\n\nPeriode: " . $awalFmt . " s/d " . $akhirFmt;
                      } else {
                        $tanggalFmt = \Carbon\Carbon::parse($sel['awal'])->translatedFormat('d M Y');
                        $tooltipText .= "\n\nTanggal: " . $tanggalFmt;
                      }
                    }
                    
                    // Tambahkan tipe izin jika ada dan berbeda
                    if (!empty($sel['tipe']) && $sel['tipe'] !== $jenisAsli) {
                      $tooltipText .= "\n\nTipe: " . $sel['tipe'];
                    }
                  } elseif ($sel['type'] === 'libur') {
                    $tooltipText = "Hari Libur: " . $sel['label'];
                  } else {
                    $tooltipText = $sel['label'];
                  }
                @endphp

                <td class="border px-1 py-1 text-xs {{ $bg }} {{ $txt }} relative"
                  data-karyawan="{{ $pegawai->id }}"
                  data-date="{{ sprintf('%04d-%02d-%02d', $tahun, $bulan, $tgl) }}"
                  @if ($sel['type'] === 'izin') data-id="{{ $sel['id'] }}"
                      data-tipe="{{ $sel['tipe'] }}"
                      data-jenis="{{ $sel['jenis'] }}"
                      data-ket="{{ $sel['ket'] }}"
                      data-file="{{ $sel['file'] }}"
                      data-awal="{{ $sel['awal'] }}"
                     data-akhir="{{ $sel['akhir'] }}" @endif
                  onclick="openIzin(this)"
                  title="{{ $tooltipText }}"
                  data-tooltip="{{ $tooltipText }}">
                  @switch($sel['type'])
                    @case('hadir')
                    @case('terlambat')
                      {{ $sel['label'] }}
                    @break

                    @case('libur')
                    @case('izin')
                      <span class="inline-block max-w-[140px] truncate">
                        {{ Str::limit($sel['label'], 25, '…') }}
                      </span>
                    @break

                    @default
                      {{-- kosong --}}
                      {{ $sel['label'] }}
                  @endswitch
                </td>
              @endforeach

              {{-- total akumulasi (hari jam menit) + span “sr-only” utk sort --}}
              @php
                $hari = intdiv($pegawai->total_menit, 1440);
                $sisa = $pegawai->total_menit % 1440;
                $jam = str_pad(intdiv($sisa, 60), 2, '0', STR_PAD_LEFT);
                $menit = str_pad($sisa % 60, 2, '0', STR_PAD_LEFT);
                $tampil = "{$hari} hari {$jam} jam {$menit} menit";
              @endphp
              <td class="border px-2 py-1 text-xs font-semibold">
                <span class="sr-only">{{ $pegawai->total_menit }}</span> {{-- untuk sort --}}
                {{ $tampil }}
              </td>

            </tr>
          @endforeach
        </tbody>
      </table>
      </div>
    </div>

    {{-- =============================================
         ALERT IZIN (custom notification)
    ============================================= --}}
    <div id="alert-izin" class="fixed top-6 left-1/2 transform -translate-x-1/2 z-50 hidden">
      <div class="bg-red-500 text-white px-6 py-3 rounded shadow-lg flex items-center gap-2">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span id="alert-izin-msg"></span>
      </div>
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
    <div id="modalConfirm" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-60 modal">
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
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
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
