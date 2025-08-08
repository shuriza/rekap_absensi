@extends('layouts.app')

{{-- =============================================================
|  Izin Presensi – DataTables (CDN) + Tailwind
|  Multi‑tabel: semua <table class="display"> otomatis aktif
============================================================= --}}

@push('styles')
    {{-- DataTables Tailwind theme (CDN) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.tailwindcss.css" />
    {{-- Override dark‑mode: paksa kontrol DataTables tetap putih --}}
    <style>
        /* =====================================================
           Paksa form‑controls DataTables tetap LIGHT, abaikan
           dark‑mode sistem (Tailwind menambah class dark:bg‑*)
        =====================================================*/
        .dataTables_wrapper, .dark .dataTables_wrapper {
            color-scheme: light;          /* hindari UA dark style */
        }

        .dataTables_wrapper select,
        .dark .dataTables_wrapper select,
        .dataTables_wrapper input[type="search"],
        .dark .dataTables_wrapper input[type="search"] {
            background-color:#ffffff !important;
            --tw-bg-opacity:1 !important;/* override Tailwind var */
            color:#1f2937 !important;    /* gray-800 */
        }

        /* pagination buttons */
        .dataTables_wrapper .dataTables_paginate .paginate_button,
        .dark .dataTables_wrapper .dataTables_paginate .paginate_button{
            background-color:#ffffff !important;
            color:#1f2937 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
        .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover{
            background-color:#f3f4f6 !important; /* gray‑100 */
        }

        /* === override utilitas dark:bg-* yang melekat pada elemen === */
        select.dark\:bg-gray-800,
        input.dark\:bg-gray-800 {
            background-color:#ffffff !important;
        }
        select.dark\:border-gray-600,
        input.dark\:border-gray-600 {
            border-color:#d1d5db !important; /* gray‑300 */
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
        .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover{
            background-color:#f3f4f6 !important; /* gray‑100 */
        }

        /* Custom styling untuk Month Picker seperti gambar */
        .flatpickr-calendar {
            font-family: inherit;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Style untuk tahun navigation */
        .flatpickr-month {
            background: transparent;
        }

        .flatpickr-prev-month,
        .flatpickr-next-month {
            color: #6b7280;
            padding: 10px;
        }

        .flatpickr-current-month {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
        }

        /* Month grid styling seperti gambar */
        .flatpickr-monthSelect-months {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding: 16px;
            background: white;
        }

        .flatpickr-monthSelect-month {
            padding: 12px 16px;
            text-align: center;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            background: white;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .flatpickr-monthSelect-month:hover {
            background-color: #f3f4f6;
            border-color: #d1d5db;
        }

        .flatpickr-monthSelect-month.selected {
            background-color: #9ca3af;
            color: white;
            border-color: #9ca3af;
            font-weight: 600;
        }

        /* Hide default calendar */
        .flatpickr-calendar .flatpickr-days {
            display: none;
        }

        .flatpickr-calendar .flatpickr-weekdays {
            display: none;
        }

        /* Custom month picker dropdown */
        #monthPickerDropdown {
            font-family: inherit;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #d1d5db;
            z-index: 9999 !important;
            position: fixed;
        }

        #monthPickerDropdown .month-btn {
            transition: all 0.2s ease;
            font-weight: 500;
        }

        #monthPickerDropdown .month-btn:hover {
            background-color: #f3f4f6;
            border-color: #9ca3af;
        }

        #monthPickerDropdown .month-btn.selected {
            background-color: #6b7280;
            color: white;
            border-color: #6b7280;
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery & DataTables (CDN) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.tailwindcss.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.js"></script>

    <script>
        // Month Selector field - dropdown version
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing month selector...');
            
            setTimeout(function() {
                const bulanInput = document.getElementById('bulanPick'); // hidden input
                const bulanDisplayInput = document.getElementById('bulanPickDisplay'); // display input
                
                if (bulanDisplayInput) {
                    console.log('Found display input element, creating dropdown month picker...');
                    
                    // Event listener untuk klik pada display input
                    bulanDisplayInput.addEventListener('click', function() {
                        console.log('Display input clicked, showing custom month picker');
                        showCustomMonthPicker(bulanInput, bulanDisplayInput);
                    });
                    
                    // Juga bisa gunakan monthSelect plugin jika tersedia
                    if (window.flatpickr && window.monthSelectPlugin && bulanInput) {
                        console.log('Using monthSelectPlugin from app.js as fallback');
                        
                        try {
                            const picker = window.flatpickr(bulanInput, {
                                plugins: [new window.monthSelectPlugin({
                                    shorthand: false,
                                    dateFormat: 'Y-m',
                                    altFormat: 'F Y',
                                    theme: 'light'
                                })],
                                dateFormat: 'Y-m',
                                altInput: true,
                                altFormat: 'F Y',
                                allowInput: false,
                                clickOpens: true,
                                static: false,
                                defaultDate: bulanInput.value ? bulanInput.value + "-01" : null,
                                
                                onChange: function(selectedDates, dateStr) {
                                    console.log('Flatpickr month selected:', dateStr);
                                    if (dateStr) {
                                        bulanInput.value = dateStr;
                                        document.getElementById('filterForm').submit();
                                    }
                                }
                            });
                            
                            console.log('Flatpickr month selector initialized as backup');
                            
                        } catch (error) {
                            console.error('Error initializing flatpickr:', error);
                        }
                    }
                } else {
                    console.error('bulanPickDisplay input not found');
                }
            }, 300);

            /* ----------   DataTables multi‑table   ---------- */
            if (window.jQuery && jQuery.fn.DataTable) {
                const dtApi = $('table.display').DataTable({
                    pageLength : 10,
                    lengthMenu : [[10,25,50,100,-1],[10,25,50,100,'Semua']],
                    order      : [[1,'asc']],          // kolom Nama
                    columnDefs : [{ targets:-1, orderable:false }],
                    responsive : true,
                    language   : {
                        search           : '',
                        searchPlaceholder: 'Cari nama / tipe…'
                    }
                });

                /* ----------   Tailwind‑ify input & select   ---------- */
                dtApi.tables().every(function () {
                    const $c = $(this.table().container());
                    $c.find('input[type="search"]').addClass('border px-3 py-2 rounded-lg border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 bg-white text-gray-700');
                    $c.find('select').addClass('border px-3 py-2 rounded-lg border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 bg-white text-gray-700');
                });
            }
        });

        // Custom month picker dropdown function
        function showCustomMonthPicker(hiddenInput, displayInput) {
            // Hapus dropdown yang sudah ada jika ada
            const existingDropdown = document.getElementById('monthPickerDropdown');
            if (existingDropdown) {
                existingDropdown.remove();
            }

            const currentYear = new Date().getFullYear();
            const currentValue = hiddenInput.value;
            let selectedYear = currentYear;
            
            // Parse existing value untuk mendapatkan tahun yang sudah dipilih
            if (currentValue) {
                const parts = currentValue.split('-');
                if (parts.length >= 1) {
                    selectedYear = parseInt(parts[0]) || currentYear;
                }
            }

            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            
            // Buat dropdown month picker
            const dropdown = document.createElement('div');
            dropdown.id = 'monthPickerDropdown';
            dropdown.className = 'bg-white border border-gray-300 rounded-lg shadow-lg';
            
            // Function untuk update posisi dropdown
            function updateDropdownPosition() {
                const rect = displayInput.getBoundingClientRect();
                dropdown.style.cssText = `
                    position: fixed;
                    top: ${rect.bottom + 4}px;
                    left: ${rect.left}px;
                    width: 300px;
                    max-height: 400px;
                    overflow-y: auto;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                    border: 1px solid #d1d5db;
                    z-index: 9999;
                    background: white;
                    border-radius: 8px;
                `;
            }
            
            // Throttled update untuk performance
            let updateTimeout;
            function throttledUpdatePosition() {
                if (updateTimeout) clearTimeout(updateTimeout);
                updateTimeout = setTimeout(updateDropdownPosition, 10);
            }
            
            // Initial positioning
            updateDropdownPosition();
            
            dropdown.innerHTML = `
                <div class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <button type="button" id="prevYear" class="text-gray-500 hover:text-gray-700 p-2 hover:bg-gray-100 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <span class="text-lg font-semibold" id="yearDisplay">${selectedYear}</span>
                        <button type="button" id="nextYear" class="text-gray-500 hover:text-gray-700 p-2 hover:bg-gray-100 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2" id="monthGrid">
                        ${months.map((month, index) => `
                            <button type="button" 
                                class="month-btn px-3 py-2 text-sm border rounded hover:bg-gray-100 text-center transition-colors" 
                                data-month="${index + 1}">
                                ${month}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
            
            document.body.appendChild(dropdown);
            
            // Highlight bulan yang sedang terpilih
            function highlightSelectedMonth() {
                const inputValue = hiddenInput.value;
                if (inputValue) {
                    const parts = inputValue.split('-');
                    if (parts.length >= 2) {
                        const month = parseInt(parts[1]);
                        const year = parseInt(parts[0]);
                        const displayYear = parseInt(document.getElementById('yearDisplay').textContent);
                        
                        // Reset semua highlight
                        dropdown.querySelectorAll('.month-btn').forEach(btn => {
                            btn.classList.remove('bg-gray-500', 'text-white');
                            btn.classList.add('hover:bg-gray-100');
                        });
                        
                        // Highlight jika tahun sama
                        if (year === displayYear) {
                            const monthBtn = dropdown.querySelector(`[data-month="${month}"]`);
                            if (monthBtn) {
                                monthBtn.classList.add('bg-gray-500', 'text-white');
                                monthBtn.classList.remove('hover:bg-gray-100');
                            }
                        }
                    }
                }
            }
            
            // Initial highlight
            highlightSelectedMonth();
            
            // Event handler untuk navigasi tahun
            const yearDisplay = dropdown.querySelector('#yearDisplay');
            const prevYearBtn = dropdown.querySelector('#prevYear');
            const nextYearBtn = dropdown.querySelector('#nextYear');
            
            prevYearBtn.addEventListener('click', function() {
                const currentYear = parseInt(yearDisplay.textContent);
                yearDisplay.textContent = currentYear - 1;
                highlightSelectedMonth();
            });
            
            nextYearBtn.addEventListener('click', function() {
                const currentYear = parseInt(yearDisplay.textContent);
                yearDisplay.textContent = currentYear + 1;
                highlightSelectedMonth();
            });
            
            // Event handlers untuk pilih bulan
            dropdown.querySelectorAll('.month-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const month = this.dataset.month.padStart(2, '0');
                    const year = yearDisplay.textContent;
                    const value = `${year}-${month}`;
                    
                    console.log('Selected:', value);
                    hiddenInput.value = value;
                    
                    // Update display input dengan format yang readable
                    const date = new Date(year, month - 1);
                    const monthName = date.toLocaleDateString('id-ID', { month: 'long' });
                    displayInput.value = `${monthName} ${year}`;
                    
                    // Bersihkan event listeners
                    window.removeEventListener('scroll', throttledUpdatePosition);
                    window.removeEventListener('resize', throttledUpdatePosition);
                    if (updateTimeout) clearTimeout(updateTimeout);
                    
                    // Submit form
                    document.getElementById('filterForm').submit();
                    
                    // Tutup dropdown
                    dropdown.remove();
                });
            });
            
            // Close dropdown jika klik di luar
            function closeOnClickOutside(e) {
                if (!dropdown.contains(e.target) && e.target !== displayInput && e.target !== hiddenInput) {
                    dropdown.remove();
                    document.removeEventListener('click', closeOnClickOutside);
                    window.removeEventListener('scroll', throttledUpdatePosition);
                    window.removeEventListener('resize', throttledUpdatePosition);
                    if (updateTimeout) clearTimeout(updateTimeout);
                }
            }
            
            // Update posisi saat scroll atau resize
            window.addEventListener('scroll', throttledUpdatePosition);
            window.addEventListener('resize', throttledUpdatePosition);
            
            // Delay sedikit untuk mencegah close langsung
            setTimeout(() => {
                document.addEventListener('click', closeOnClickOutside);
            }, 100);
            
            // Close on escape key
            function closeOnEscape(e) {
                if (e.key === 'Escape') {
                    dropdown.remove();
                    document.removeEventListener('keydown', closeOnEscape);
                    window.removeEventListener('scroll', throttledUpdatePosition);
                    window.removeEventListener('resize', throttledUpdatePosition);
                    if (updateTimeout) clearTimeout(updateTimeout);
                }
            }
            
            document.addEventListener('keydown', closeOnEscape);
        }
    </script>
@endpush

@section('content')
@php 
    $bt = request('bulan_tahun', now()->format('Y-m')); 
@endphp

<div class="min-h-screen flex flex-col px-6 py-4">

    <h1 class="text-lg font-semibold mb-4">
      Daftar Izin Presensi Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu
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
          Filter & Kelola Data
        </h3>
        <span class="text-sm text-gray-500">Kelola izin presensi karyawan</span>
      </div>
      
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        {{-- Filter Section --}}
        <div class="flex flex-wrap items-end gap-4">
          {{-- Periode picker --}}
          <div class="space-y-2 relative">
            <label for="bulanPick" class="block text-sm font-medium text-gray-700 flex items-center">
              <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              Periode
            </label>
            <form id="filterForm" method="GET" action="{{ route('izin_presensi.index') }}" class="relative">
              <input id="bulanPick" name="bulan_tahun" type="hidden" 
                value="{{ request('bulan_tahun') ? request('bulan_tahun') : '' }}" />
              <input id="bulanPickDisplay" type="text" 
                value="{{ request('bulan_tahun') ? \Carbon\Carbon::createFromFormat('Y-m', request('bulan_tahun'))->translatedFormat('F Y') : '' }}" 
                class="w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors cursor-pointer" 
                placeholder="Klik untuk pilih bulan" 
                readonly />
            </form>
          </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap gap-3">
          {{-- Buat Izin --}}
          <a href="{{ route('izin_presensi.create') }}" 
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Buat Izin Baru
          </a>

          {{-- Export XLSX --}}
          <a href="{{ route('export.izin.bulanan', ['bulan_tahun'=>request('bulan_tahun'), 'sort'=>'nama_asc']) }}" 
            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Excel
          </a>

          {{-- Reset --}}
          <a href="{{ route('izin_presensi.index') }}" 
            class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Reset Filter
          </a>
        </div>
      </div>

      {{-- Active Filters Indicator --}}
      @if(request('bulan_tahun'))
        <div class="mt-4 pt-4 border-t border-gray-200">
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">Filter aktif:</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              Periode: {{ \Carbon\Carbon::createFromFormat('Y-m', request('bulan_tahun'))->translatedFormat('F Y') }}
              <a href="{{ route('izin_presensi.index') }}" class="ml-1 hover:text-blue-600">&times;</a>
            </span>
          </div>
        </div>
      @else
        <div class="mt-4 pt-4 border-t border-gray-200">
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">Status:</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Menampilkan semua data izin dari semua periode
            </span>
          </div>
        </div>
      @endif
    </div>

    {{-- ========================================================
        MODERN TABLE CONTAINER
    ========================================================= --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      {{-- Table Header --}}
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Data Izin Presensi
          </h3>

          <div class="flex items-center space-x-2 text-sm text-gray-500">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              {{ $data->count() }} Izin
            </span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
              @if(!request('bulan_tahun'))
                Semua Data
              @else
                {{ \Carbon\Carbon::createFromFormat('Y-m', $bt)->translatedFormat('F Y') }}
              @endif
            </span>
          </div>
        </div>
      </div>

      {{-- Table Content --}}
      <div class="overflow-x-auto">
        <table class="display min-w-full text-sm" id="izinTable">
          <thead class="bg-gradient-to-r from-gray-100 to-gray-200">
            <tr>
              <th class="w-12 px-4 py-3 text-left font-semibold text-gray-800 uppercase">
                <div class="flex items-center space-x-1">
                  <span>No</span>
                </div>
              </th>
              <th class="px-4 py-3 text-left font-semibold text-gray-800 uppercase">
                <div class="flex items-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  <span>Karyawan</span>
                </div>
              </th>
              <th class="w-24 px-4 py-3 text-center font-semibold text-gray-800 uppercase">
                <div class="flex items-center justify-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a1.994 1.994 0 01-1.414.586H7a4 4 0 01-4-4V7a4 4 0 014-4z"></path>
                  </svg>
                  <span>Tipe</span>
                </div>
              </th>
              <th class="w-36 px-4 py-3 text-center font-semibold text-gray-800 uppercase">
                <div class="flex items-center justify-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  <span>Periode</span>
                </div>
              </th>
              <th class="w-28 px-4 py-3 text-center font-semibold text-gray-800 uppercase">
                <div class="flex items-center justify-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 7a2 2 0 00-2 2v2m0 0V9a2 2 0 012-2h14a2 2 0 012 2v2M7 7h10"></path>
                  </svg>
                  <span>Jenis</span>
                </div>
              </th>
              <th class="w-24 px-4 py-3 text-center font-semibold text-gray-800 uppercase">
                <div class="flex items-center justify-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                  </svg>
                  <span>Berkas</span>
                </div>
              </th>
              <th class="px-4 py-3 text-center font-semibold text-gray-800 uppercase">
                <div class="flex items-center justify-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                  </svg>
                  <span>Keterangan</span>
                </div>
              </th>
              <th class="w-32 px-4 py-3 text-center font-semibold text-gray-800 uppercase">
                <div class="flex items-center justify-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                  </svg>
                  <span>Aksi</span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
                    @foreach($data as $i => $izin)
                        <tr class="hover:bg-green-50 odd:bg-white even:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $i+1 }}</td>
                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                <div class="font-medium">{{ $izin->karyawan->nama }}</div>
                                <div class="text-xs text-gray-500">{{ $izin->karyawan->departemen }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $izin->tipe_ijin }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ $izin->tanggal_awal->format('d-m-Y') }}
                                @if($izin->tanggal_akhir)
                                    <span class="mx-0.5">–</span> {{ $izin->tanggal_akhir->format('d-m-Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $izin->jenis_ijin }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($izin->berkas)
                                    <a href="{{ route('izin_presensi.lampiran', $izin) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-600 rounded-full hover:bg-blue-200 transition" title="Lihat lampiran">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Lampiran
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 whitespace-pre-line">{{ $izin->keterangan ?: '—' }}</td>
                            <td class="px-4 py-3 text-center space-x-1">
                                <a href="{{ route('izin_presensi.show', $izin) }}" 
                                   class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-xs font-medium transition-all duration-200">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Detail
                                </a>
                                <form action="{{ route('izin_presensi.destroy', $izin) }}" method="POST" class="inline hapus-form">
                                    @csrf @method('DELETE')
                                    <button type="button" 
                                            class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-xs font-medium transition-all duration-200 btn-hapus-izin" 
                                            data-id="{{ $izin->id }}">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- =============================================
     FOOTER
============================================= --}}
    <footer class="text-center py-4 text-sm text-gray-600 mt-8">
      Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu &middot;
      @if(!request('bulan_tahun'))
        Semua Data Izin Presensi
      @else
        Izin Presensi {{ \Carbon\Carbon::createFromFormat('Y-m', $bt)->translatedFormat('F Y') }}
      @endif
    </footer>
</div>

{{-- =============================================
     MODERN CONFIRMATION MODAL
============================================= --}}
<div id="modalConfirm" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-60 modal">
  <div class="flex items-center justify-center min-h-screen px-4">
    <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
      {{-- Modal Header --}}
      <div class="flex items-center justify-between pb-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
          <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Konfirmasi Hapus
        </h3>
        <button onclick="closeModal('modalConfirm')" type="button"
          class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      {{-- Modal Content --}}
      <div class="mt-6 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
          <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
          </svg>
        </div>
        <h4 class="text-lg font-medium text-gray-900 mb-2">Hapus Data Izin</h4>
        <p class="text-sm text-gray-600 mb-6">
          Apakah Anda yakin ingin menghapus data izin ini? Tindakan ini tidak dapat dibatalkan.
        </p>

        {{-- Action Buttons --}}
        <div class="flex justify-center space-x-3">
          <button onclick="closeModal('modalConfirm')" 
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            Batal
          </button>
          <button id="btn-confirm-hapus" 
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
            Ya, Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
{{-- ========= END MODAL KONFIRMASI ========= --}}

@push('scripts')
<script>
    let pendingDeleteForm = null;
    document.addEventListener('DOMContentLoaded', function() {
        // Ganti tombol hapus agar buka modal
        document.querySelectorAll('.btn-hapus-izin').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                pendingDeleteForm = btn.closest('form');
                openModal('modalConfirm');
            });
        });
        // Konfirmasi hapus
        document.getElementById('btn-confirm-hapus').addEventListener('click', function() {
            if (pendingDeleteForm) {
                pendingDeleteForm.submit();
                pendingDeleteForm = null;
                closeModal('modalConfirm');
            }
        });
    });
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
@endpush

</div>
@endsection
