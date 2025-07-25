@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.tailwindcss.css" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.tailwindcss.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            /* Bulan–Tahun picker */
            flatpickr('#bulanPick', {
                plugins: [new monthSelectPlugin({
                    shorthand : true,
                    dateFormat: 'Y-m',   // value: 2025-04
                    altFormat : 'F Y'    // display: April 2025
                })],
                onChange() { document.getElementById('filterForm').submit(); }
            });

            /* DataTables init */
            const dt = $('#izinTable').DataTable({
                pageLength : 10,
                lengthMenu : [[10,25,50,100,-1],[10,25,50,100,'Semua']],
                order      : [[1,'asc']],      // default sort: Nama (A→Z)
                columnDefs : [{targets:-1, orderable:false}],
                responsive : true,
                language   : {
                    search           : '',
                    searchPlaceholder: 'Cari nama / tipe…'
                }
            });

            // Tailwind‑ify search input & length select
            const $container = $(dt.table().container());
            $container.find('input[type="search"]').addClass('border px-3 py-2 rounded-lg border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 bg-white text-gray-700');
            $container.find('select[name="izinTable_length"]').addClass('border px-3 py-2 rounded-lg border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 bg-white text-gray-700');
        });
    </script>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header & Global Actions --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800">Daftar Izin Presensi</h2>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
            {{-- Search stub (hidden, DataTables will replace) --}}
            <input type="search" placeholder="Cari nama / tipe…" class="hidden" />

            {{-- Bulan picker --}}
            <form id="filterForm" method="GET" action="{{ route('izin_presensi.index') }}" class="flex items-end gap-2">
                @php $bt = request('bulan_tahun', now()->format('Y-m')); @endphp
                <label class="flex flex-col text-sm">
                    <span class="mb-1 font-medium">Bulan</span>
                    <input id="bulanPick" name="bulan_tahun" type="text" value="{{ $bt }}" class="border border-gray-300 p-2 rounded w-40 bg-white" placeholder="Pilih Bulan" />
                </label>
            </form>

            {{-- Buat Izin --}}
            <a href="{{ route('izin_presensi.create') }}" class="flex items-center gap-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Buat Izin
            </a>

            {{-- Export & Reset --}}
            <a href="{{ route('export.izin.bulanan', ['bulan_tahun'=>$bt, 'sort'=>'nama_asc']) }}" class="inline-flex items-center gap-1 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded shadow text-sm transition" title="Export XLSX">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                XLSX
            </a>
            <a href="{{ route('izin_presensi.index') }}" class="inline-flex items-center gap-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded shadow text-sm transition">Reset</a>
        </div>
    </div>

    {{-- Tabel Izin --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table id="izinTable" class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="w-12 px-4 py-3 text-left font-semibold text-gray-600 uppercase">No</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Karyawan</th>
                        <th class="w-24 px-4 py-3 text-center font-semibold text-gray-600 uppercase">Tipe</th>
                        <th class="w-36 px-4 py-3 text-center font-semibold text-gray-600 uppercase">Periode</th>
                        <th class="w-28 px-4 py-3 text-center font-semibold text-gray-600 uppercase">Jenis</th>
                        <th class="w-24 px-4 py-3 text-center font-semibold text-gray-600 uppercase">Berkas</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 uppercase">Keterangan</th>
                        <th class="w-32 px-4 py-3 text-center font-semibold text-gray-600 uppercase">Aksi</th>
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
                                <a href="{{ route('izin_presensi.show', $izin) }}" class="inline-block px-2 py-1 bg-emerald-100 text-emerald-700 rounded hover:bg-emerald-200 text-xs transition">Detail</a>
                                <form action="{{ route('izin_presensi.destroy', $izin) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-xs transition">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection