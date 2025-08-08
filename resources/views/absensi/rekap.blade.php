<?php /* resources/views/absensi/rekap.blade.php (UPDATED with modal izin) */ ?>

@extends('layouts.app')

@php
  use Illuminate\Support\Str;
@endphp

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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Rekap Bulanan
          </a>
          
          <a href="{{ route('absensi.rekap.tahunan') }}"
            class="group inline-flex items-center py-2 px-1 border-b-2 font-medium text-sm {{ request()->is('absensi/rekap-tahunan') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <svg class="w-5 h-5 mr-2 {{ request()->is('absensi/rekap-tahunan') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
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
      
      {{-- Update bagian form pencarian --}}
      <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="filter-form">
        {{-- Bulan --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h2a2 2 0 002-2z"></path>
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

        {{-- Cari Nama dengan tombol pencarian --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Cari Nama
          </label>
          <div class="relative flex">
            <div class="relative flex-1">
              <input type="text" 
                name="search" 
                value="{{ request('search') }}"

                placeholder="Ketik nama pegawai..." 
                onkeypress="handleSearchKeypress(event)"
                class="w-full pl-10 pr-4 py-2 rounded-l-md border-gray-300 border-r-0 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors" />
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
            </div>
            
            {{-- Tombol Cari --}}
            <button type="submit" 
              class="inline-flex items-center px-3 py-2 border border-gray-300 border-l-0 rounded-r-md bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium text-gray-700 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              <span class="ml-1 hidden sm:inline">Cari</span>
            </button>
            
            {{-- Tombol Clear (hanya tampil jika ada pencarian) --}}
            @if(request('search'))
              <button type="button" 
                onclick="clearSearch()" 
                class="inline-flex items-center px-2 py-2 ml-1 border border-gray-300 rounded-md bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm font-medium text-red-700 transition-colors"
                title="Hapus pencarian">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
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
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Nama: "{{ request('search') }}"
                <button type="button" onclick="clearSearch()" class="ml-1 hover:text-blue-600 font-bold">×</button>
              </span>
            @endif
            @if(request('segment', 1) != 1)
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                </svg>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h2a2 2 0 002-2z"></path>
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
      <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
      
      <style>
        /* Pastikan tabel menggunakan fixed layout untuk kolom yang konsisten */
        #tabel-rekap {
          table-layout: fixed !important;
          width: 100% !important;
        }
        
        /* Fixed width untuk kolom No - DIPERBESAR sedikit agar tidak scroll */
        #tabel-rekap th:first-child,
        #tabel-rekap td:first-child {
          width: 35px !important;
          min-width: 35px !important;
          max-width: 35px !important;
        }
        
        /* Kolom Nama - DIPERKECIL sedikit untuk kompensasi */
        #tabel-rekap th:nth-child(2),
        #tabel-rekap td:nth-child(2) {
          width: 100px !important;
          min-width: 100px !important;
          max-width: 100px !important;
        }
        
        /* Kolom tanggal - DIPERKECIL sedikit untuk fit dalam viewport */
        #tabel-rekap th:not(:first-child):not(:nth-child(2)):not(:last-child),
        #tabel-rekap td:not(:first-child):not(:nth-child(2)):not(:last-child) {
          width: 75px !important;
          min-width: 75px !important;
          max-width: 75px !important;
        }
        
        /* Kolom Total - DIPERKECIL untuk kompensasi */
        #tabel-rekap th:last-child,
        #tabel-rekap td:last-child {
          width: 110px !important;
          min-width: 110px !important;
          max-width: 110px !important;
        }

        /* Style khusus untuk nama karyawan - text truncate */
        #tabel-rekap td:nth-child(2) {
          text-overflow: ellipsis;
          white-space: nowrap;
          overflow: hidden;
          font-size: 12px;
        }

        /* Font size untuk header No dan Nama */
        #tabel-rekap th:first-child,
        #tabel-rekap th:nth-child(2) {
          font-size: 12px;
        }

        /* Font size untuk header kolom tanggal - TETAP */
        #tabel-rekap th:not(:first-child):not(:nth-child(2)):not(:last-child) {
          font-size: 11px;
        }

        /* Font size untuk kolom No */
        #tabel-rekap td:first-child {
          font-size: 12px;
        }

        /* Font size untuk konten kolom tanggal (jam) - TETAP */
        #tabel-rekap td:not(:first-child):not(:nth-child(2)):not(:last-child) {
          font-size: 11px !important;
        }

        /* Font size untuk header dan konten Total Akumulasi - TETAP */
        #tabel-rekap th:last-child {
          font-size: 11px;
        }

        #tabel-rekap td:last-child {
          font-size: 11px;
        }

        /* Pastikan text wrapping yang baik */
        #tabel-rekap td {
          word-wrap: break-word;
          overflow-wrap: break-word;
        }

        /* Responsive table untuk memastikan fit di layar */
        .table-container {
          max-width: 100vw;
          overflow-x: auto;
        }

        /* Responsive adjustments untuk layar yang sangat kecil */
        @media (max-width: 1200px) {
          #tabel-rekap th:not(:first-child):not(:nth-child(2)):not(:last-child),
          #tabel-rekap td:not(:first-child):not(:nth-child(2)):not(:last-child) {
            width: 70px !important;
            min-width: 70px !important;
            max-width: 70px !important;
          }
          
          #tabel-rekap th:nth-child(2),
          #tabel-rekap td:nth-child(2) {
            width: 90px !important;
            min-width: 90px !important;
            max-width: 90px !important;
          }
          
          #tabel-rekap th:last-child,
          #tabel-rekap td:last-child {
            width: 100px !important;
            min-width: 100px !important;
            max-width: 100px !important;
          }
        }

        /* Custom Tooltip Styles - existing styles remain unchanged */
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
        // Function untuk handle keypress pada input pencarian
        function handleSearchKeypress(event) {
          if (event.key === 'Enter') {
            event.preventDefault();
            document.getElementById('filter-form').submit();
          }
        }
        
        // Function untuk clear pencarian
        function clearSearch() {
          const searchInput = document.querySelector('input[name="search"]');
          searchInput.value = '';
          document.getElementById('filter-form').submit();
        }
        
        // Focus pada input pencarian saat halaman dimuat jika ada pencarian aktif
        document.addEventListener('DOMContentLoaded', function() {
          const searchInput = document.querySelector('input[name="search"]');
          if (searchInput && searchInput.value) {
            searchInput.focus();
            // Set cursor di akhir text
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
          }
        });

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
            📤 Export Excel
            <span class="ml-2 text-xs bg-green-500 px-2 py-0.5 rounded-full">
              {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('M') }} {{ $tahun }}
            </span>
          </a>

          {{-- OB Management --}}
          <button onclick="openObModal()" 
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
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

    {{-- =============================================
         Cek apakah ada data absensi yang valid di bulan ini
    ============================================= --}}
    @php
      $hasValidAttendanceData = false;
      
      // Loop semua karyawan untuk cek data absensi
      foreach ($pegawaiList as $pegawai) {
        foreach ($tanggalList as $tgl) {
          $sel = $pegawai->absensi_harian[$tgl] ?? ['type' => 'kosong', 'label' => '-'];
          
          // Jika ada data selain kosong dan libur, berarti ada data absensi valid
          if ($sel['type'] !== 'kosong' && $sel['type'] !== 'libur') {
            $hasValidAttendanceData = true;
            break 2; // keluar dari kedua loop
          }
        }
      }
    @endphp

    {{-- ========================================================
        MODERN TABLE CONTAINER
    ========================================================= --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      {{-- Table Header - Selalu tampil --}}
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
            </svg>
            Rekap Absensi Karyawan
          </h3>

          {{-- Legend selalu tampil --}}
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

          {{-- Info karyawan and bulan selalu tampil --}}
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

      {{-- Tampilkan tabel hanya jika ada data absensi --}}
      @if($hasValidAttendanceData)
        {{-- Table Content dengan container responsif --}}
        <div class="overflow-x-auto table-container">
          <table id="tabel-rekap" class="min-w-full text-center border-collapse display nowrap">
            <thead class="bg-gradient-to-r from-gray-100 to-gray-200">
              <tr>
                <th class="border border-gray-300 w-9 px-1 py-3 cursor-pointer text-gray-800 font-semibold hover:bg-gray-200 transition-colors" 
                  onclick="resetUrutan()" title="Klik untuk reset urutan">
                  <div class="flex items-center justify-center space-x-1">
                    <span class="text-xs">No</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                    </svg>
                  </div>
                </th>
                <th class="border border-gray-300 w-25 px-1 py-3 text-gray-800 font-semibold">
                  <div class="flex items-center justify-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-xs">Nama</span>
                  </div>
                </th>

                {{-- Header kolom tanggal dengan lebar yang disesuaikan --}}
                @foreach ($tanggalList as $tgl)
                  <th class="border border-gray-300 w-19 min-w-[75px] px-1 py-3 no-sort text-gray-800 font-medium text-xs">
                    {{ $tgl }}
                  </th>
                @endforeach

                <th class="border border-gray-300 w-28 px-2 py-3 text-gray-800 font-semibold">
                  <span class="text-xs">Total Akumulasi</span>
                </th>
              </tr>
            </thead>

            {{-- Update bagian body tabel dengan Tailwind classes yang disesuaikan --}}
            <tbody class="bg-white text-gray-800">
              @foreach ($pegawaiList as $pegawai)
                <tr class="hover:bg-gray-50">
                  {{-- No dengan lebar yang disesuaikan --}}
                  <td class="border border-gray-300 px-1 py-1 w-9 text-xs">{{ $loop->iteration }}</td>
                  
                  {{-- Nama dengan lebar yang disesuaikan --}}
                  <td class="border border-gray-300 px-1 py-1 text-left w-25 text-xs" 
                      title="{{ $pegawai->nama }}">
                    {{ Str::limit($pegawai->nama, 10, '...') }}
                  </td>

                  {{-- Kolom tanggal dengan lebar yang disesuaikan --}}
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

                      // Buat tooltip lengkap untuk izin
                      $tooltipText = '';
                      if ($sel['type'] === 'izin') {
                        $jenisLengkap = $jenisLengkap ?? [];
                        $jenisAsli = $sel['jenis'] ?? 'Tidak ada jenis';
                        
                        $jenisTooltip = $jenisAsli;
                        foreach ($jenisLengkap as $lengkap) {
                          if (str_starts_with($lengkap, $jenisAsli) || 
                              str_contains($lengkap, explode(' ', $jenisAsli)[0])) {
                            $jenisTooltip = $lengkap;
                            break;
                          }
                        }
                        
                        $tooltipText = "Jenis: " . $jenisTooltip;
                        
                        if (!empty($sel['ket']) && $sel['ket'] !== '-') {
                          $tooltipText .= "\n\nKeterangan: " . $sel['ket'];
                        }
                        
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
                        
                        if (!empty($sel['tipe']) && $sel['tipe'] !== $jenisAsli) {
                          $tooltipText .= "\n\nTipe: " . $sel['tipe'];
                        }
                      } elseif ($sel['type'] === 'libur') {
                        $tooltipText = "Hari Libur: " . $sel['label'];
                      } else {
                        $tooltipText = $sel['label'];
                      }
                    @endphp

                    <td class="border border-gray-300 px-1 py-1 text-xs {{ $bg }} {{ $txt }} w-19 min-w-[75px] relative group"
                      data-karyawan="{{ $pegawai->id }}"
                      data-date="{{ sprintf('%04d-%02d-%02d', $tahun, $bulan, $tgl) }}"
                      @if ($sel['type'] === 'izin') 
                        data-id="{{ $sel['id'] }}"
                        data-tipe="{{ $sel['tipe'] }}"
                        data-jenis="{{ $sel['jenis'] }}"
                        data-ket="{{ $sel['ket'] }}"
                        data-file="{{ $sel['file'] }}"
                        data-awal="{{ $sel['awal'] }}"
                        data-akhir="{{ $sel['akhir'] }}" 
                      @endif
                      onclick="openIzin(this)"
                      title="{{ $tooltipText }}"
                      data-tooltip="{{ $tooltipText }}">
                      
                      {{-- Konten sel dengan font size yang TIDAK BERUBAH --}}
                      @switch($sel['type'])
                        @case('hadir')
                        @case('terlambat')
                          <div class="text-center leading-tight break-words font-medium">
                            {{ $sel['label'] }}
                          </div>
                        @break

                        @case('libur')
                          <div class="text-center leading-tight break-words">
                            {{ Str::limit($sel['label'], 8, '...') }}
                          </div>
                        @break

                        @case('izin')
                          <div class="text-center leading-tight break-words cursor-pointer">
                            {{ Str::limit($sel['label'], 8, '...') }}
                          </div>
                          
                          {{-- Tooltip untuk izin menggunakan Tailwind CSS sesuai project pattern --}}
                          @if(!empty($tooltipText))
                            <div class="absolute z-50 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-300 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 whitespace-pre-line bottom-full left-1/2 transform -translate-x-1/2 mb-2 min-w-[200px] max-w-[300px] shadow-lg pointer-events-none">
                              {{ $tooltipText }}
                              {{-- Arrow using Tailwind utilities --}}
                              <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                            </div>
                          @endif
                        @break

                        @default
                          <div class="text-center leading-tight break-words">
                            {{ $sel['label'] }}
                          </div>
                      @endswitch
                    </td>
                  @endforeach

                  {{-- Total akumulasi dengan lebar yang disesuaikan --}}
                  @php
                    $hari = intdiv($pegawai->total_menit, 1440);
                    $sisa = $pegawai->total_menit % 1440;
                    $jam = str_pad(intdiv($sisa, 60), 2, '0', STR_PAD_LEFT);
                    $menit = str_pad($sisa % 60, 2, '0', STR_PAD_LEFT);
                    $tampil = "{$hari} hari {$jam} jam {$menit} menit";
                  @endphp
                  <td class="border border-gray-300 px-1 py-1 text-xs font-semibold w-28" 
                      title="{{ $tampil }}">
                    <span class="sr-only">{{ $pegawai->total_menit }}</span>
                    {{ $tampil }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        {{-- Tampilan kosong ketika tidak ada data absensi --}}
        <div class="text-center py-16 px-6">
          <div class="max-w-md mx-auto">
            {{-- Empty Icon --}}
            <div class="mx-auto w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-6">
              <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            
            {{-- Empty Message --}}
            <h4 class="text-xl font-medium text-gray-900 mb-3">
              Belum Ada Data Absensi
            </h4>
            
            <p class="text-gray-500 text-base leading-relaxed mb-6">
              Belum ada karyawan yang melakukan absensi di bulan 
              <span class="font-semibold text-gray-700">{{ \Carbon\Carbon::create($tahun, $bulan)->translatedFormat('F Y') }}</span>
            </p>
            
            {{-- Additional Info --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-center justify-center space-x-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm text-blue-700 font-medium">
                  Tabel akan muncul otomatis setelah ada data absensi
                </p>
              </div>
            </div>
          </div>
        </div>
      @endif

      {{-- Pastikan penutupan div container --}}
    </div>

    {{-- =============================================
     MODAL IZIN PRESENSI
============================================= --}}
<div id="modal-overlay" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-60 modal">
  <div class="flex items-center justify-center min-h-screen px-4">
    <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
      {{-- Modal Header --}}
      <div class="flex items-center justify-between pb-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
          <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          Form Izin Presensi
        </h3>
        <button onclick="closeIzin()" type="button"
          class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      {{-- Modal Content --}}
      <form id="form-izin" action="{{ route('izin_presensi.store') }}" method="POST" enctype="multipart/form-data" class="mt-6">
        @csrf
        
        {{-- Hidden Fields --}}
        <input type="hidden" name="karyawan_id" id="izin-karyawan" />
        
        {{-- Tanggal Awal --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Awal</label>
          <input type="date" name="tanggal_awal" id="izin-awal" required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
        </div>

        {{-- Tanggal Akhir --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
          <input type="date" name="tanggal_akhir" id="izin-akhir" required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
        </div>

        {{-- Tipe Izin --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Izin</label>
          <select name="tipe" id="tipe-ijin" required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            <option value="">Pilih tipe izin</option>
            <option value="Izin">Izin</option>
            <option value="Sakit">Sakit</option>
            <option value="Cuti">Cuti</option>
          </select>
        </div>

        {{-- Jenis Izin --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Izin</label>
          <input type="text" name="jenis" id="jenis-ijin" 
            placeholder="Contoh: Keperluan Pribadi, Sakit Demam, dll"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
        </div>

        {{-- Keterangan --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
          <textarea name="keterangan" id="keterangan-izin" rows="3"
            placeholder="Keterangan tambahan (opsional)"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
        </div>

        {{-- File Lampiran --}}
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">File Lampiran</label>
          <input type="file" name="file_lampiran" accept="image/*,.pdf,.doc,.docx"
            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
          <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, PDF, DOC (Max: 2MB)</p>
          <div id="preview-lampiran" class="mt-2 text-sm text-blue-600"></div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex justify-between space-x-3">
          <button type="button" id="btn-hapus" onclick="showDeleteConfirm(this)" 
            class="hidden px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
            🗑️ Hapus
          </button>
          
          <div class="flex space-x-3 ml-auto">
            <button type="button" onclick="closeIzin()" 
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
              Batal
            </button>
            <button type="submit" id="btn-simpan"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
              💾 Simpan
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- =============================================
     MODAL KONFIRMASI HAPUS
============================================= --}}
<div id="modalConfirm" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-60 modal">
  <div class="flex items-center justify-center min-h-screen px-4">
    <div class="relative bg-white rounded-xl shadow-2xl max-w-sm w-full p-6">
      <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
          <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v6m-6 0h12M4 4h16v16H4z"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Konfirmasi Hapus</h3>
        <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus data izin ini? Tindakan ini tidak dapat dibatalkan.</p>
        
        <div class="flex space-x-3">
          <button type="button" onclick="closeModal('modalConfirm')" 
            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            Batal
          </button>
          <button type="button" onclick="deleteConfirmed()" 
            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
            Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- =============================================
     ALERT NOTIFICATION
============================================= --}}
<div id="alert-izin" class="fixed top-4 right-4 z-50 hidden">
  <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg max-w-sm">
    <div class="flex items-center">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <span id="alert-izin-msg"></span>
    </div>
  </div>
</div>

@endsection