@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
    {{-- Header Section --}}
    <div class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Dashboard Analytics</h1>
                            <p class="text-gray-600 mt-1">Analisis performa kehadiran & evaluasi karyawan</p>
                        </div>
                    </div>
                    
                    {{-- Info Banner --}}
                    <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-400 rounded-r-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Sistem Evaluasi Fair v2.0:</strong> Menggunakan composite scoring dengan bobot konsisten untuk menghindari bias. 
                                    Semua ranking mengecualikan hari izin dari perhitungan. Karyawan OB memiliki sistem penilaian khusus berdasarkan kelengkapan jam kerja.
                                </p>
                                <div class="mt-2 text-xs text-blue-600">
                                    <strong>Formula Standar:</strong> Kehadiran (60%) + Metrik Spesifik (40%) | 
                                    <strong>Threshold:</strong> Hijau ≥85, Biru ≥70, Kuning ≥55, Merah <55
                                </div>
                                
                                {{-- Collapse/Expand Button --}}
                                <button type="button" 
                                        onclick="toggleFormulaDetail()"
                                        class="mt-3 inline-flex items-center px-3 py-1.5 border border-blue-300 text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                    <span id="toggleText">Lihat Detail Formula</span>
                                    <svg id="toggleIcon" class="ml-1 h-3 w-3 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                {{-- Detailed Formula Explanation --}}
                                <div id="formulaDetail" class="hidden mt-4 p-4 bg-white rounded-lg border border-blue-200">
                                    <h4 class="text-sm font-bold text-gray-900 mb-3">📊 Penjelasan Detail Formula Perangkingan</h4>
                                    
                                    {{-- Top Karyawan Terbaik --}}
                                    <div class="mb-4 p-3 bg-green-50 rounded-lg border-l-4 border-green-400">
                                        <h5 class="text-xs font-bold text-green-800 mb-2">🏆 TOP KARYAWAN TERBAIK</h5>
                                        <div class="text-xs text-gray-700 space-y-1">
                                            <p><strong>Formula:</strong> Composite Score = (Attendance Rate × 0.6) + (Punctuality Rate × 0.4)</p>
                                            <p><strong>Attendance Rate:</strong> (Hari Hadir + Hari Izin) ÷ Total Hari Kerja × 100%</p>
                                            <p><strong>Punctuality Rate:</strong> Hari Tepat Waktu ÷ (Hari Kerja - Hari Izin) × 100%</p>
                                            <p><strong>Syarat:</strong> Min. attendance 60% & min. 5 hari kerja efektif</p>
                                            <p><strong>Contoh:</strong> Attendance 90% + Punctuality 80% = (90×0.6) + (80×0.4) = 54 + 32 = <strong>86 poin</strong></p>
                                        </div>
                                    </div>
                                    
                                    {{-- Karyawan Penalty --}}
                                    <div class="mb-4 p-3 bg-purple-50 rounded-lg border-l-4 border-purple-400">
                                        <h5 class="text-xs font-bold text-purple-800 mb-2">⚡ KARYAWAN PENALTY TERBANYAK</h5>
                                        <div class="text-xs text-gray-700 space-y-1">
                                            <p><strong>Formula:</strong> Composite Score = (Attendance Rate × 0.6) + (Penalty Control Score × 0.4)</p>
                                            <p><strong>Penalty Control Score:</strong> max(0, (120 - Avg Penalty Per Day) ÷ 120 × 100%)</p>
                                            <p><strong>Attendance Rate:</strong> Hari Hadir ÷ (Hari Kerja - Hari Izin) × 100%</p>
                                            <p><strong>Syarat:</strong> Min. 5 hari kerja efektif & ada record penalty</p>
                                            <p><strong>Contoh:</strong> Attendance 80%, Avg Penalty 60 menit/hari = (80×0.6) + (50×0.4) = 48 + 20 = <strong>68 poin</strong></p>
                                        </div>
                                    </div>
                                    
                                    {{-- Karyawan Terlambat --}}
                                    <div class="mb-4 p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                                        <h5 class="text-xs font-bold text-yellow-800 mb-2">⚠️ KARYAWAN SERING TERLAMBAT</h5>
                                        <div class="text-xs text-gray-700 space-y-1">
                                            <p><strong>Formula:</strong> Composite Score = (Attendance Rate × 0.6) + (Late Control Score × 0.4)</p>
                                            <p><strong>Late Control Score:</strong> max(0, 100 - Persentase Terlambat)</p>
                                            <p><strong>Persentase Terlambat:</strong> Hari Terlambat ÷ Hari Hadir × 100%</p>
                                            <p><strong>Syarat:</strong> Min. 5 hari kerja efektif & ada record terlambat</p>
                                            <p><strong>Contoh:</strong> Attendance 75%, Terlambat 40% = (75×0.6) + (60×0.4) = 45 + 24 = <strong>69 poin</strong></p>
                                        </div>
                                    </div>
                                    
                                    {{-- Karyawan Tidak Masuk --}}
                                    <div class="mb-4 p-3 bg-red-50 rounded-lg border-l-4 border-red-400">
                                        <h5 class="text-xs font-bold text-red-800 mb-2">❌ KARYAWAN SERING TIDAK MASUK</h5>
                                        <div class="text-xs text-gray-700 space-y-1">
                                            <p><strong>Formula:</strong> Composite Score = (Attendance Rate × 0.6) + (Absence Control Score × 0.4)</p>
                                            <p><strong>Absence Control Score:</strong> max(0, 100 - Persentase Tidak Masuk)</p>
                                            <p><strong>Persentase Tidak Masuk:</strong> Hari Tidak Masuk ÷ (Hari Kerja - Hari Izin) × 100%</p>
                                            <p><strong>Syarat:</strong> Min. 5 hari kerja efektif & ada record tidak masuk</p>
                                            <p><strong>Contoh:</strong> Attendance 60%, Tidak Masuk 30% = (60×0.6) + (70×0.4) = 36 + 28 = <strong>64 poin</strong></p>
                                        </div>
                                    </div>
                                    
                                    {{-- Sistem Penilaian --}}
                                    <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-gray-400">
                                        <h5 class="text-xs font-bold text-gray-800 mb-2">🎯 SISTEM PENILAIAN & KETERANGAN</h5>
                                        <div class="text-xs text-gray-700 space-y-2">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p><strong>Rentang Skor:</strong></p>
                                                    <p class="text-green-600">• 85-100: Excellent (Hijau)</p>
                                                    <p class="text-blue-600">• 70-84: Good (Biru)</p>
                                                    <p class="text-yellow-600">• 55-69: Fair (Kuning)</p>
                                                    <p class="text-red-600">• 0-54: Poor (Merah)</p>
                                                </div>
                                                <div>
                                                    <p><strong>Prinsip Fair:</strong></p>
                                                    <p>• Hari izin tidak dihitung sebagai penalti</p>
                                                    <p>• Minimum data untuk ranking yang valid</p>
                                                    <p>• Bobot konsisten 60:40 untuk semua kategori</p>
                                                    <p>• Karyawan OB punya sistem khusus</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Filter Section --}}
                <div class="mt-6 lg:mt-0 lg:ml-8">
                    <form method="GET" class="bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Periode</label>
                                <div class="flex gap-3">
                                    <select name="bulan" class="block w-36 px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                            </option>
                                        @endfor
                                    </select>
                                    <select name="tahun" class="block w-20 px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                                        @for ($i = 2024; $i <= \Carbon\Carbon::now()->year + 1; $i++)
                                            <option value="{{ $i }}" {{ $tahun == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Statistics Overview --}}
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-600 rounded-full mr-3"></div>
                Ringkasan Statistik
            </h2>
        
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                {{-- Total Karyawan --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 transform hover:scale-105 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $statistikUmum['total_karyawan'] }}</h3>
                            <p class="text-sm font-medium text-gray-600 mt-1">Total Karyawan</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                {{-- Total Kehadiran --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 transform hover:scale-105 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $statistikUmum['total_kehadiran'] }}</h3>
                            <p class="text-sm font-medium text-gray-600 mt-1">Total Kehadiran</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-green-400 to-green-600 rounded-lg shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                {{-- Total Terlambat --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 transform hover:scale-105 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $statistikUmum['total_terlambat'] }}</h3>
                            <p class="text-sm font-medium text-gray-600 mt-1">Total Terlambat</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                {{-- Total Izin --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 transform hover:scale-105 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $statistikUmum['total_izin'] }}</h3>
                            <p class="text-sm font-medium text-gray-600 mt-1">Total Izin</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                {{-- Total Tidak Masuk --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 transform hover:scale-105 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $statistikUmum['total_tidak_masuk'] }}</h3>
                            <p class="text-sm font-medium text-gray-600 mt-1">Tidak Masuk</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-red-400 to-red-600 rounded-lg shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

        {{-- Charts Section --}}
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <div class="w-1 h-6 bg-gradient-to-b from-purple-500 to-pink-600 rounded-full mr-3"></div>
                Analisis Visual
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Chart Kehadiran per Departemen --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <div class="p-2 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                            </svg>
                        </div>
                        Kehadiran per Departemen
                    </h3>
                    <div class="h-80">
                        <canvas id="departemenChart"></canvas>
                    </div>
                </div>
                
                {{-- Chart Trend Absensi --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <div class="p-2 bg-gradient-to-r from-green-400 to-emerald-500 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        Trend Absensi {{ $tahun }}
                    </h3>
                    <div class="h-80">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grid Layout for Rankings --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            {{-- Top Performers Section --}}
            <div class="space-y-8">
                {{-- Top 10 Karyawan Terbaik --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-r from-green-400 to-emerald-500 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">🏆 Top 10 Karyawan Terbaik</h3>
                                <p class="text-xs text-green-700">Composite Score: Kehadiran (60%) + Punctuality (40%)</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kehadiran</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Punctuality</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($topKaryawanPunctual as $index => $karyawan)
                                    <tr class="{{ $index < 3 ? 'bg-green-50' : '' }}">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                @if($index == 0)
                                                    <span class="text-yellow-500 text-lg">🥇</span>
                                                @elseif($index == 1)
                                                    <span class="text-gray-400 text-lg">🥈</span>
                                                @elseif($index == 2)
                                                    <span class="text-yellow-600 text-lg">🥉</span>
                                                @else
                                                    <span class="text-gray-600 font-medium text-sm">{{ $index + 1 }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $karyawan->nama }}</div>
                                            <div class="text-xs text-gray-500">{{ $karyawan->departemen }}</div>
                                            @if($karyawan->is_ob)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    OB
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-xs text-gray-700">{{ $karyawan->total_hadir ?? 0 }}/{{ $karyawan->hari_kerja_tanpa_izin ?? 0 }}</div>
                                            <div class="text-xs font-medium text-green-600">{{ $karyawan->attendance_rate ?? 0 }}%</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-xs font-medium text-blue-600">{{ $karyawan->punctuality_rate ?? 0 }}%</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-bold {{ ($karyawan->composite_score ?? 0) >= 85 ? 'text-green-600' : (($karyawan->composite_score ?? 0) >= 70 ? 'text-blue-600' : (($karyawan->composite_score ?? 0) >= 55 ? 'text-yellow-600' : 'text-red-600')) }}">>
                                                {{ number_format($karyawan->composite_score ?? 0, 1) }}
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center">
                                            <div class="text-gray-500">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-7 7-7-7m14 8l-7 7-7-7" />
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
                                                <p class="mt-1 text-sm text-gray-500">Data karyawan tidak tersedia bulan ini</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Top Karyawan Penalty --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-r from-purple-400 to-indigo-600 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">⚡ Karyawan Penalty Terbanyak</h3>
                                <p class="text-xs text-purple-700">Composite Score: Kehadiran (60%) + Kontrol Penalty (40%)</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penalty</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($topKaryawanPenalty as $index => $karyawan)
                                    <tr class="{{ $index < 3 ? 'bg-purple-50' : '' }}">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $index + 1 }}</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $karyawan->nama }}</div>
                                            <div class="text-xs text-gray-500">{{ $karyawan->departemen }}</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-purple-600">{{ $karyawan->penalty_hours_display ?? 0 }}</div>
                                            <div class="text-xs text-gray-500">/jam</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-bold {{ ($karyawan->composite_score ?? 0) >= 85 ? 'text-green-600' : (($karyawan->composite_score ?? 0) >= 70 ? 'text-blue-600' : (($karyawan->composite_score ?? 0) >= 55 ? 'text-yellow-600' : 'text-red-600')) }}">>
                                                {{ number_format($karyawan->composite_score ?? 0, 1) }}
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-8 text-center">
                                            <div class="text-gray-500">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data penalty</h3>
                                                <p class="mt-1 text-sm text-gray-500">Semua karyawan disiplin bulan ini!</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Problem Areas Section --}}
            <div class="space-y-8">
                {{-- Top Karyawan Terlambat --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">⚠️ Karyawan Sering Terlambat</h3>
                                <p class="text-xs text-yellow-700">Composite Score: Kehadiran (60%) + Kontrol Keterlambatan (40%)</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kehadiran</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terlambat</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($topKaryawanTerlambat as $index => $karyawan)
                                    <tr class="{{ $index < 3 ? 'bg-red-50' : '' }}">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $index + 1 }}</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $karyawan->nama }}</div>
                                            <div class="text-xs text-gray-500">{{ $karyawan->departemen }}</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-xs text-gray-700">{{ $karyawan->total_hadir ?? 0 }}/{{ $karyawan->hari_kerja_efektif ?? 0 }}</div>
                                            <div class="text-xs text-gray-500">{{ $karyawan->persentase_kehadiran ?? 0 }}%</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-red-600">{{ $karyawan->total_terlambat ?? 0 }}x</div>
                                            <div class="text-xs text-gray-500">({{ $karyawan->persentase_terlambat ?? 0 }}%)</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-bold {{ ($karyawan->composite_score ?? 0) >= 85 ? 'text-green-600' : (($karyawan->composite_score ?? 0) >= 70 ? 'text-blue-600' : (($karyawan->composite_score ?? 0) >= 55 ? 'text-yellow-600' : 'text-red-600')) }}">
                                                {{ number_format($karyawan->composite_score ?? 0, 1) }}
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center">
                                            <div class="text-gray-500">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada keterlambatan</h3>
                                                <p class="mt-1 text-sm text-gray-500">Semua karyawan disiplin waktu!</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Top Karyawan Tidak Masuk --}}
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-r from-red-400 to-pink-500 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">❌ Karyawan Sering Tidak Masuk</h3>
                                <p class="text-xs text-red-700">Composite Score: Kehadiran (60%) + Kontrol Absensi (40%)</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tidak Masuk</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($topKaryawanTidakMasuk as $index => $karyawan)
                                    <tr class="{{ $index < 3 ? 'bg-red-50' : '' }}">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $index + 1 }}</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $karyawan->nama }}</div>
                                            <div class="text-xs text-gray-500">{{ $karyawan->departemen }}</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-red-600">{{ $karyawan->total_tidak_masuk ?? 0 }}x</div>
                                            <div class="text-xs text-gray-500">({{ $karyawan->persentase_tidak_masuk ?? 0 }}%)</div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-sm font-bold {{ ($karyawan->composite_score ?? 0) >= 85 ? 'text-green-600' : (($karyawan->composite_score ?? 0) >= 70 ? 'text-blue-600' : (($karyawan->composite_score ?? 0) >= 55 ? 'text-yellow-600' : 'text-red-600')) }}">
                                                {{ $karyawan->composite_score ?? 0 }}
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-8 text-center">
                                            <div class="text-gray-500">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada ketidakhadiran</h3>
                                                <p class="mt-1 text-sm text-gray-500">Semua karyawan hadir atau izin!</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Chart Scripts --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- Data JSON untuk JavaScript --}}
<script type="application/json" id="dashboard-data">
{
    "departemenData": {!! json_encode($kehadiranPerDepartemen) !!},
    "trendData": {!! json_encode($trendAbsensi) !!},
    "tahun": {{ $tahun }}
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ambil data dari JSON script
    const dashboardData = JSON.parse(document.getElementById('dashboard-data').textContent);
    const departemenData = dashboardData.departemenData;
    const trendData = dashboardData.trendData;
    const tahun = dashboardData.tahun;
    
    // Chart Kehadiran per Departemen
    const departemenCtx = document.getElementById('departemenChart').getContext('2d');
    
    new Chart(departemenCtx, {
        type: 'bar',
        data: {
            labels: departemenData.map(d => d.departemen),
            datasets: [
                {
                    label: 'Hadir',
                    data: departemenData.map(d => d.total_hadir || 0),
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Terlambat',
                    data: departemenData.map(d => d.total_terlambat || 0),
                    backgroundColor: 'rgba(251, 191, 36, 0.8)',
                    borderColor: 'rgba(251, 191, 36, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Izin',
                    data: departemenData.map(d => d.total_izin || 0),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Tidak Masuk',
                    data: departemenData.map(d => d.total_tidak_masuk || 0),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Kehadiran per Departemen - ' + new Date().toLocaleDateString('id-ID', {month: 'long', year: 'numeric'})
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // Chart Trend Absensi
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.bulan),
            datasets: [
                {
                    label: 'Hadir',
                    data: trendData.map(d => d.hadir),
                    borderColor: 'rgba(34, 197, 94, 1)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Terlambat',
                    data: trendData.map(d => d.terlambat),
                    borderColor: 'rgba(251, 191, 36, 1)',
                    backgroundColor: 'rgba(251, 191, 36, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Izin',
                    data: trendData.map(d => d.izin),
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Tidak Masuk',
                    data: trendData.map(d => d.tidak_masuk || 0),
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Trend Absensi Bulanan ' + tahun
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 10
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
});

// Function untuk toggle formula detail
function toggleFormulaDetail() {
    const detail = document.getElementById('formulaDetail');
    const icon = document.getElementById('toggleIcon');
    const text = document.getElementById('toggleText');
    
    if (detail.classList.contains('hidden')) {
        detail.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
        text.textContent = 'Sembunyikan Detail Formula';
    } else {
        detail.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
        text.textContent = 'Lihat Detail Formula';
    }
}
</script>
@endpush
@endsection
