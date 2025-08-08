{{-- resources/views/absensi/index.blade.php --}}
@extends('layouts.app')
@push('styles')
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.tailwindcss.css" />
  <style>
    /* Custom Calendar Styles */
    .calendar-date {
      @apply p-3 text-center text-sm cursor-pointer rounded-lg transition-all duration-200 border border-transparent;
      min-height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 500;
    }

    .calendar-date:hover {
      @apply bg-purple-50 border-purple-200 scale-105 shadow-sm;
    }

    .calendar-date.selected-start {
      @apply bg-green-600 text-white font-bold shadow-lg border-green-600;
      background: linear-gradient(135deg, #16a34a, #15803d);
    }

    .calendar-date.selected-end {
      @apply bg-red-600 text-white font-bold shadow-lg border-red-600;
      background: linear-gradient(135deg, #dc2626, #b91c1c);
    }

    .calendar-date.in-range {
      @apply bg-yellow-300 text-yellow-900 font-semibold border-yellow-400;
      background: linear-gradient(135deg, #fde047, #facc15);
    }

    .calendar-date.today {
      @apply border-2 border-blue-400 bg-blue-50 font-bold text-blue-700;
    }

    .calendar-date.today.selected-start {
      @apply border-2 border-white;
      background: linear-gradient(135deg, #16a34a, #15803d);
    }

    .calendar-date.today.selected-end {
      @apply border-2 border-white;
      background: linear-gradient(135deg, #dc2626, #b91c1c);
    }

    .calendar-date.today.in-range {
      @apply border-2 border-yellow-600;
      background: linear-gradient(135deg, #fde047, #facc15);
    }

    .calendar-fade-in {
      animation: fadeInCalendar 0.3s ease-out;
    }

    @keyframes fadeInCalendar {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Smooth transitions for month changes */
    #calendarDates {
      transition: opacity 0.2s ease-in-out;
    }

    /* Active state for calendar dates */
    .calendar-date:active {
      @apply transform scale-95;
    }

    /* Enhanced hover effects */
    .calendar-date.selected-start:hover,
    .calendar-date.selected-end:hover,
    .calendar-date.in-range:hover {
      @apply scale-110 shadow-xl;
    }

    /* Custom scrollbar for file list */
    #file_list::-webkit-scrollbar {
      width: 6px;
    }

    #file_list::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 3px;
    }

    #file_list::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 3px;
    }

    #file_list::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
  </style>
@endpush

@push('scripts')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

  <script src="https://cdn.datatables.net/2.3.2/js/dataTables.tailwindcss.js"></script>


  <script>
    document.addEventListener('DOMContentLoaded', () => {
      $('#absensiTable').DataTable({
        dom: 't',
        ordering: true,
        stateSave: true,
        pageLength: 40,
        columnDefs: [{
          targets: [0, 4, 5, 6],
          orderable: false
        }],
        responsive: true
      });
    });
  </script>
@endpush

@section('content')
  <div class="w-full mx-auto mt-10 space-y-6">
    {{-- Filter Jam & Upload --}}
    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2 flex items-center">
          ⏰ Filter Jam Masuk & Pulang
        </h2>
        <p class="text-gray-600">Atur filter jam absensi untuk analisis data karyawan</p>
      </div>

      @if (session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
      @endif
      @if (session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
      @endif
      @if (session('debug_ramadhan'))
        <div class="bg-blue-100 text-blue-700 p-3 rounded mb-4">
          <strong>Debug:</strong> {{ session('debug_ramadhan') }}
        </div>
      @endif
      @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
          <ul class="list-disc ml-5">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('absensi.preview') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

          {{-- Senin – Kamis --}}
          <div
            class="bg-white shadow-md rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-shadow">
            <h3 class="text-xl font-semibold mb-6 text-gray-800 flex items-center">
              📅 Filter Jam Absensi: Senin – Kamis
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Masuk Minimal</label>
                <div class="relative">
                  <input type="time" name="jam_masuk_min_senin"
                    value="{{ old('jam_masuk_min_senin', '07:00') }}"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-colors" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Masuk Maksimal</label>
                <div class="relative">
                  <input type="time" name="jam_masuk_max_senin"
                    value="{{ old('jam_masuk_max_senin', '07:30') }}"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-colors" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pulang Minimal</label>
                <div class="relative">
                  <input type="time" name="jam_pulang_min_senin"
                    value="{{ old('jam_pulang_min_senin', '15:30') }}"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-colors" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pulang Maksimal</label>
                <div class="relative">
                  <input type="time" name="jam_pulang_max_senin"
                    value="{{ old('jam_pulang_max_senin', '17:00') }}"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-colors" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Jumat --}}
          <div
            class="bg-white shadow-md rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-shadow">
            <h3 class="text-xl font-semibold mb-6 text-gray-800 flex items-center">
              🕌 Filter Jam Absensi: Jumat
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Masuk Minimal</label>
                <div class="relative">
                  <input type="time" name="jam_masuk_min_jumat"
                    value="{{ old('jam_masuk_min_jumat', '06:30') }}"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-colors" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Masuk Maksimal</label>
                <div class="relative">
                  <input type="time" name="jam_masuk_max_jumat"
                    value="{{ old('jam_masuk_max_jumat', '07:00') }}"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-colors" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pulang Minimal</label>
                <div class="relative">
                  <input type="time" name="jam_pulang_min_jumat"
                    value="{{ old('jam_pulang_min_jumat', '14:30') }}"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-colors" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pulang Maksimal</label>
                <div class="relative">
                  <input type="time" name="jam_pulang_max_jumat"
                    value="{{ old('jam_pulang_max_jumat', '16:00') }}"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-colors" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        {{-- Tombol Toggle Ramadhan --}}
        <div class="flex justify-center mb-6">
          <button type="button" id="toggleRamadhanFilter"
            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105">
            🌙 Tampilkan Filter Khusus Ramadhan
          </button>
        </div>

        {{-- Section Ramadhan (hidden default) --}}
        <div id="ramadhanSection" class="hidden space-y-6 mb-6">
          {{-- Grid untuk Kalender dan Filter Jam --}}
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Rentang Tanggal Ramadhan --}}
            <div
              class="lg:col-span-1 bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl border border-purple-200 shadow-sm">
              <label class="flex items-center text-lg font-semibold text-purple-800 mb-4">
                🌙 Pilih Periode Ramadhan
              </label>

              <!-- Input untuk menampilkan hasil pilihan -->
              <input type="text" id="ramadhan_display"
                placeholder="Klik pada kalender untuk memilih periode Ramadhan" readonly
                class="w-full p-3 mb-4 border border-purple-300 rounded-lg bg-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-500 font-medium text-purple-800" />

              <!-- Hidden inputs untuk form submission -->
              <input type="hidden" name="ramadhan_start_date" id="ramadhan_start_date"
                value="{{ old('ramadhan_start_date') }}" />
              <input type="hidden" name="ramadhan_end_date" id="ramadhan_end_date"
                value="{{ old('ramadhan_end_date') }}" />

              <!-- Custom Calendar -->
              <div class="bg-white border border-purple-200 rounded-xl p-4 shadow-sm">
                <!-- Calendar Header -->
                <div class="flex items-center justify-between mb-4">
                  <button type="button" id="prevMonth"
                    class="p-2 hover:bg-purple-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 19l-7-7 7-7"></path>
                    </svg>
                  </button>
                  <h3 id="monthYear" class="text-lg font-semibold text-purple-800"></h3>
                  <button type="button" id="nextMonth"
                    class="p-2 hover:bg-purple-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5l7 7-7 7"></path>
                    </svg>
                  </button>
                </div>

                <!-- Days of week header -->
                <div class="grid grid-cols-7 gap-1 mb-2">
                  <div class="text-center text-xs font-medium text-gray-500 p-2">Min</div>
                  <div class="text-center text-xs font-medium text-gray-500 p-2">Sen</div>
                  <div class="text-center text-xs font-medium text-gray-500 p-2">Sel</div>
                  <div class="text-center text-xs font-medium text-gray-500 p-2">Rab</div>
                  <div class="text-center text-xs font-medium text-gray-500 p-2">Kam</div>
                  <div class="text-center text-xs font-medium text-gray-500 p-2">Jum</div>
                  <div class="text-center text-xs font-medium text-gray-500 p-2">Sab</div>
                </div>

                <!-- Calendar dates -->
                <div id="calendarDates" class="grid grid-cols-7 gap-1 mb-4"></div>

                <!-- Action buttons -->
                <div class="flex justify-between items-center pt-3 border-t border-purple-100">
                  <button type="button" id="clearSelection"
                    class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1 rounded-md hover:bg-gray-100 transition-colors">
                    🗑️ Hapus Pilihan
                  </button>
                  <div class="text-xs text-purple-600 font-medium">
                    📅 🟢 Mulai • 🟡 Periode • 🔴 Selesai
                  </div>
                </div>
              </div>
            </div>

            {{-- Filter Jam Ramadhan --}}
            <div class="lg:col-span-2 space-y-6">
              {{-- Ramadhan: Senin – Kamis --}}
              <div
                class="bg-white shadow-md rounded-xl p-6 border-2 border-purple-200 hover:shadow-lg transition-shadow">
                <h3 class="text-xl font-semibold mb-6 text-purple-800 flex items-center">
                  🌙 Filter Jam Ramadhan: Senin – Kamis
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Masuk Minimal</label>
                    <div class="relative">
                      <input type="time" name="jam_masuk_min_ramadhan_senin"
                        value="{{ old('jam_masuk_min_ramadhan_senin', '08:00') }}"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                      <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">

                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Masuk
                      Maksimal</label>
                    <div class="relative">
                      <input type="time" name="jam_masuk_max_ramadhan_senin"
                        value="{{ old('jam_masuk_max_ramadhan_senin', '08:30') }}"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                      <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">

                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pulang
                      Minimal</label>
                    <div class="relative">
                      <input type="time" name="jam_pulang_min_ramadhan_senin"
                        value="{{ old('jam_pulang_min_ramadhan_senin', '15:00') }}"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                      <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">

                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pulang
                      Maksimal</label>
                    <div class="relative">
                      <input type="time" name="jam_pulang_max_ramadhan_senin"
                        value="{{ old('jam_pulang_max_ramadhan_senin', '16:00') }}"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                      <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">

                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Ramadhan: Jumat --}}
              <div
                class="bg-white shadow-md rounded-xl p-6 border-2 border-purple-200 hover:shadow-lg transition-shadow">
                <h3 class="text-xl font-semibold mb-6 text-purple-800 flex items-center">
                  🌙 Filter Jam Ramadhan: Jumat
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Masuk Minimal</label>
                    <div class="relative">
                      <input type="time" name="jam_masuk_min_ramadhan_jumat"
                        value="{{ old('jam_masuk_min_ramadhan_jumat', '08:00') }}"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                      <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">

                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Masuk
                      Maksimal</label>
                    <div class="relative">
                      <input type="time" name="jam_masuk_max_ramadhan_jumat"
                        value="{{ old('jam_masuk_max_ramadhan_jumat', '08:30') }}"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                      <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">

                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pulang
                      Minimal</label>
                    <div class="relative">
                      <input type="time" name="jam_pulang_min_ramadhan_jumat"
                        value="{{ old('jam_pulang_min_ramadhan_jumat', '15:00') }}"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                      <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">

                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pulang
                      Maksimal</label>
                    <div class="relative">
                      <input type="time" name="jam_pulang_max_ramadhan_jumat"
                        value="{{ old('jam_pulang_max_ramadhan_jumat', '16:00') }}"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                      <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        {{-- <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg> --}}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
    </div>

    {{-- UPLOAD FILE --}}
    <div class="bg-white p-6 rounded-xl shadow border">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
        📄 Upload File Excel
      </h3>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File Excel</label>
          <div class="flex items-center space-x-4">
            <button type="button" onclick="document.getElementById('file_input').click()"
              class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
              <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4v16m8-8H4" />
              </svg>
              Tambah File
            </button>
            <span id="file_count"
              class="text-gray-600 text-sm font-medium bg-gray-100 px-3 py-1 rounded-full">0
              file</span>
          </div>
          <input id="file_input" type="file" name="file_excel[]" multiple class="hidden" />
        </div>

        <div>
          <ul id="file_list"
            class="border border-gray-200 rounded-lg divide-y divide-gray-200 bg-gray-50 max-h-40 overflow-y-auto">
          </ul>
        </div>

        <div class="pt-4 border-t border-gray-200">
          <button type="submit"
            class="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none"
              viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Preview Data
          </button>
        </div>
      </div>
    </div>
    </form>

    @push('scripts')
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          // Toggle Ramadhan Filter
          const btn = document.getElementById('toggleRamadhanFilter');
          const section = document.getElementById('ramadhanSection');

          btn.addEventListener('click', () => {
            section.classList.toggle('hidden');
            if (section.classList.contains('hidden')) {
              btn.textContent = '🌙 Tampilkan Filter Khusus Ramadhan';
            } else {
              btn.textContent = '❌ Sembunyikan Filter Khusus Ramadhan';
            }
          });

          // Custom Calendar Implementation
          let currentDate = new Date();
          let selectedStartDate = null;
          let selectedEndDate = null;

          const monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
          ];

          function formatDateIndonesia(date) {
            const options = {
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            };
            return date.toLocaleDateString('id-ID', options);
          }

          function formatDateISO(date) {
            return date.toISOString().split('T')[0];
          }

          function updateDisplay() {
            const displayInput = document.getElementById('ramadhan_display');
            const startInput = document.getElementById('ramadhan_start_date');
            const endInput = document.getElementById('ramadhan_end_date');

            if (selectedStartDate && selectedEndDate) {
              displayInput.value =
                `${formatDateIndonesia(selectedStartDate)} - ${formatDateIndonesia(selectedEndDate)}`;
              displayInput.style.color = '#7c3aed'; // Purple color for completed selection
              displayInput.style.fontWeight = '600';
              startInput.value = formatDateISO(selectedStartDate);
              endInput.value = formatDateISO(selectedEndDate);
            } else if (selectedStartDate) {
              displayInput.value =
                `Mulai: ${formatDateIndonesia(selectedStartDate)} (Pilih tanggal akhir)`;
              displayInput.style.color = '#8b5cf6'; // Lighter purple for partial selection
              displayInput.style.fontWeight = '500';
              startInput.value = formatDateISO(selectedStartDate);
              endInput.value = '';
            } else {
              displayInput.value = '';
              displayInput.placeholder = 'Klik pada kalender untuk memilih periode Ramadhan';
              displayInput.style.color = '';
              displayInput.style.fontWeight = '';
              startInput.value = '';
              endInput.value = '';
            }
          }

          function renderCalendar() {
            const monthYear = document.getElementById('monthYear');
            const calendarDates = document.getElementById('calendarDates');

            monthYear.textContent =
              `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;

            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            const startingDayOfWeek = firstDay.getDay();
            const today = new Date();

            calendarDates.innerHTML = '';

            // Debug: Log selected dates
            console.log('Selected Start:', selectedStartDate);
            console.log('Selected End:', selectedEndDate);

            // Add empty cells for days before first day of month
            for (let i = 0; i < startingDayOfWeek; i++) {
              const emptyDiv = document.createElement('div');
              emptyDiv.className = 'p-2';
              calendarDates.appendChild(emptyDiv);
            }

            // Add days of the month
            for (let day = 1; day <= lastDay.getDate(); day++) {
              const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
              const dateDiv = document.createElement('div');
              dateDiv.className = 'calendar-date';
              dateDiv.textContent = day;
              dateDiv.style.cursor = 'pointer';

              // Check if this is today
              if (date.toDateString() === today.toDateString()) {
                dateDiv.classList.add('today');
              }

              // Styling for selected dates and range - use date comparison instead of string
              let isSelected = false;

              if (selectedStartDate && date.getTime() === selectedStartDate.getTime()) {
                dateDiv.classList.add('selected-start');
                dateDiv.setAttribute('title', '🟢 Tanggal Mulai Ramadhan');
                // Force inline styles as backup
                dateDiv.style.backgroundColor = '#16a34a';
                dateDiv.style.color = 'white';
                dateDiv.style.fontWeight = 'bold';
                dateDiv.style.borderColor = '#16a34a';
                isSelected = true;
                console.log(`Day ${day} marked as start date`);
              } else if (selectedEndDate && date.getTime() === selectedEndDate.getTime()) {
                dateDiv.classList.add('selected-end');
                dateDiv.setAttribute('title', '🔴 Tanggal Selesai Ramadhan');
                // Force inline styles as backup
                dateDiv.style.backgroundColor = '#dc2626';
                dateDiv.style.color = 'white';
                dateDiv.style.fontWeight = 'bold';
                dateDiv.style.borderColor = '#dc2626';
                isSelected = true;
                console.log(`Day ${day} marked as end date`);
              } else if (selectedStartDate && selectedEndDate && date.getTime() > selectedStartDate
                .getTime() && date.getTime() < selectedEndDate.getTime()) {
                dateDiv.classList.add('in-range');
                dateDiv.setAttribute('title', '🟡 Dalam Periode Ramadhan');
                // Force inline styles as backup
                dateDiv.style.backgroundColor = '#facc15';
                dateDiv.style.color = '#92400e';
                dateDiv.style.fontWeight = '500';
                dateDiv.style.borderColor = '#facc15';
                isSelected = true;
                console.log(`Day ${day} marked as in-range`);
              } else {
                dateDiv.setAttribute('title',
                  `Pilih tanggal ${day} ${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`
                );
                // Reset inline styles for unselected dates
                dateDiv.style.backgroundColor = '';
                dateDiv.style.color = '';
                dateDiv.style.fontWeight = '';
                dateDiv.style.borderColor = '';
              }

              // Add click event with visual feedback
              dateDiv.addEventListener('click', (e) => {
                // Add click animation
                e.target.style.transform = 'scale(0.95)';
                setTimeout(() => {
                  e.target.style.transform = '';
                }, 100);

                const clickedDate = new Date(currentDate.getFullYear(), currentDate.getMonth(),
                  day);

                if (!selectedStartDate) {
                  selectedStartDate = clickedDate;
                  console.log('Set start date:', selectedStartDate);
                } else if (!selectedEndDate) {
                  if (clickedDate.getTime() >= selectedStartDate.getTime()) {
                    selectedEndDate = clickedDate;
                    console.log('Set end date:', selectedEndDate);
                  } else {
                    selectedStartDate = clickedDate;
                    selectedEndDate = null;
                    console.log('Reset start date:', selectedStartDate);
                  }
                } else {
                  selectedStartDate = clickedDate;
                  selectedEndDate = null;
                  console.log('New start date:', selectedStartDate);
                }

                updateDisplay();
                renderCalendar();
              });

              // Add hover effect for better UX (only for unselected dates)
              dateDiv.addEventListener('mouseenter', () => {
                if (!isSelected) {
                  dateDiv.style.backgroundColor = '#f3e8ff';
                  dateDiv.style.transform = 'scale(1.05)';
                }
              });

              dateDiv.addEventListener('mouseleave', () => {
                if (!isSelected) {
                  dateDiv.style.backgroundColor = '';
                  dateDiv.style.transform = '';
                }
              });

              calendarDates.appendChild(dateDiv);
            }

            // Add fade-in animation
            calendarDates.classList.add('calendar-fade-in');
            setTimeout(() => {
              calendarDates.classList.remove('calendar-fade-in');
            }, 300);
          }

          // Navigation buttons
          const prevBtn = document.getElementById('prevMonth');
          const nextBtn = document.getElementById('nextMonth');

          prevBtn.addEventListener('click', () => {
            // Add click animation
            prevBtn.style.transform = 'scale(0.95)';
            setTimeout(() => {
              prevBtn.style.transform = '';
            }, 100);

            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
          });

          nextBtn.addEventListener('click', () => {
            // Add click animation
            nextBtn.style.transform = 'scale(0.95)';
            setTimeout(() => {
              nextBtn.style.transform = '';
            }, 100);

            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
          });

          // Clear selection button
          const clearBtn = document.getElementById('clearSelection');
          clearBtn.addEventListener('click', () => {
            // Add click animation
            clearBtn.style.transform = 'scale(0.95)';
            setTimeout(() => {
              clearBtn.style.transform = '';
            }, 100);

            selectedStartDate = null;
            selectedEndDate = null;
            updateDisplay();
            renderCalendar();
          });

          // Load old values if exist
          const oldStartDate = document.getElementById('ramadhan_start_date').value;
          const oldEndDate = document.getElementById('ramadhan_end_date').value;

          if (oldStartDate) {
            selectedStartDate = new Date(oldStartDate);
          }
          if (oldEndDate) {
            selectedEndDate = new Date(oldEndDate);
          }

          // Initialize calendar
          renderCalendar();
          updateDisplay();

          // File input management
          const fileInput = document.getElementById('file_input');
          const fileList = document.getElementById('file_list');
          const fileCount = document.getElementById('file_count');
          const dt = new DataTransfer();

          function renderFiles() {
            const count = dt.files.length;
            fileCount.textContent = `${count} file${count !== 1 ? 's' : ''}`;
            fileList.innerHTML = '';

            if (count === 0) {
              fileList.innerHTML =
                '<li class="px-4 py-8 text-center text-gray-500 italic">Belum ada file yang dipilih</li>';
              return;
            }

            Array.from(dt.files).forEach((file, i) => {
              const li = document.createElement('li');
              li.className =
                'flex justify-between items-center px-4 py-3 hover:bg-gray-50 transition-colors';
              li.innerHTML = `
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <div>
                    <span class="text-gray-800 text-sm font-medium">${file.name}</span>
                    <div class="text-xs text-gray-500">${(file.size / 1024).toFixed(1)} KB</div>
                  </div>
                </div>
                <button type="button" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-full transition-colors" data-idx="${i}" title="Hapus file">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              `;
              fileList.appendChild(li);
            });
          }

          fileInput.addEventListener('change', () => {
            for (const f of fileInput.files) dt.items.add(f);
            fileInput.files = dt.files;
            renderFiles();
          });

          fileList.addEventListener('click', e => {
            if (e.target.matches('button[data-idx]')) {
              dt.items.remove(Number(e.target.dataset.idx));
              fileInput.files = dt.files;
              renderFiles();
            }
          });
        });
      </script>
    @endpush

  </div>

  {{-- Preview Table --}}
  @if (!empty($preview) && $preview->count())
    <div class="bg-white p-6 rounded-xl shadow border">
      <h2 class="text-xl font-bold mb-4">📄 Preview Data Absensi</h2>
      <p class="text-sm text-gray-600 mb-2">Menampilkan {{ $preview->total() }} data
        absensi.</p>

      <form method="GET" action="{{ route('absensi.preview') }}"
        class="mb-4 md:flex-row gap-2 md:items-center justify-between">
        <input type="text" name="search" placeholder="Cari nama..."
          value="{{ request('search') }}" class="border p-2 rounded w-full md:w-1/3" />
        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
          Cari
        </button>
      </form>

      <form method="POST" action="{{ route('absensi.store') }}">
        @csrf
        <table id="absensiTable" class="min-w-full text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-2 py-1 text-left">No</th>
              <th class="px-2 py-1 text-left">Nama</th>
              <th class="px-2 py-1 text-left">Departemen</th>
              <th class="px-2 py-1 text-left">Tanggal</th>
              <th class="px-2 py-1 text-left">Jam Masuk</th>
              <th class="px-2 py-1 text-left">Jam Pulang</th>
              <th class="px-2 py-1 text-left">Keterangan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            @foreach ($preview as $row)
              <tr>
                <td class="px-2 py-1">

                  {{ ($preview->currentPage() - 1) * $preview->perPage() + $loop->iteration }}
                </td>
                <td class="px-2 py-1">{{ $row['nama'] }}</td>
                <td class="px-2 py-1">{{ $row['departemen'] }}</td>
                <td class="px-2 py-1">{{ $row['tanggal'] }}</td>
                <td class="px-2 py-1">{{ $row['jam_masuk'] }}</td>
                <td class="px-2 py-1">{{ $row['jam_pulang'] }}</td>
                <td class="px-2 py-1">{{ $row['keterangan'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="flex items-center justify-between flex-col">
          <button type="submit"
            class="bg-green-600 text-white px-4 py-2 my-4 rounded hover:bg-green-700">
            Simpan ke Database
          </button>
          <p class="text-sm text-gray-600 my-4">
            Showing {{ $preview->firstItem() }} to {{ $preview->lastItem() }} of
            {{ $preview->total() }} results
          </p>
          <div class="mt-2">{{ $preview->links() }}</div>
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
