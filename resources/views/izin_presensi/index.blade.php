@extends('layouts.app')

@push('styles') <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.4/dist/tailwind.min.css" rel="stylesheet">
@endpush

@section('content')

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4 sm:mb-0">Daftar Izin Presensi</h2>
        <div class="flex space-x-2">
            <!-- Tombol Buat -->
            <a href="{{ route('izin_presensi.create') }}"
               class="inline-flex items-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg shadow-md transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Pesan Baru
            </a>
            <!-- Search Input (opsional Livewire) -->
            <div class="relative">
                <input type="search" wire:model.debounce.300ms="search"
                       placeholder="Cari karyawan atau tipe..."
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent text-sm" />
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

```
<!-- Table Card -->
<div class="bg-white shadow-lg rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-100">
                <tr>
                    <th class="w-12 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Karyawan</th>
                    <th class="w-24 px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase">Tipe</th>
                    <th class="w-36 px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase">Periode</th>
                    <th class="w-28 px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase">Jenis</th>
                    <th class="w-24 px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase">Berkas</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase">Keterangan</th>
                    <th class="w-32 px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($data as $i => $izin)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $data->firstItem() + $i }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <div class="font-medium">{{ $izin->karyawan->nama }}</div>
                            <div class="text-xs text-gray-500">{{ $izin->karyawan->nip }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-center text-gray-700">{{ $izin->tipe_ijin }}</td>
                        <td class="px-4 py-3 text-sm text-center text-gray-700">
                            {{ $izin->tanggal_awal->format('d-m-Y') }}
                            @if($izin->tanggal_akhir)
                                – {{ $izin->tanggal_akhir->format('d-m-Y') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-center text-gray-700">{{ $izin->jenis_ijin }}</td>
                        <!-- Kolom Berkas -->
                        <td class="px-4 py-3 text-center text-sm">
                            @if($izin->berkas)
                                <a href="{{ Storage::url($izin->berkas) }}" target="_blank"
                                   class="inline-block px-2 py-1 bg-blue-100 text-blue-600 rounded-full text-xs hover:bg-blue-200 transition">
                                    Lihat Lampiran
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <!-- Kolom Keterangan -->
                        <td class="px-4 py-3 text-sm text-center text-gray-700 whitespace-pre-line">
                            {{ $izin->keterangan ?: '-' }}
                        </td>
                        <!-- Kolom Aksi -->
                        <td class="px-4 py-3 text-sm text-center space-x-1">
                            <!-- Detail -->
                            <a href="{{ route('izin_presensi.show', $izin) }}"
                               class="inline-block px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs hover:bg-emerald-200">
                                Detail
                            </a>
                            <!-- Hapus -->
                            <form action="{{ route('izin_presensi.destroy', $izin) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Yakin hapus?');">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">Belum ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <div class="px-4 py-4 bg-gray-50">
        {{ $data->links('pagination::tailwind') }}
    </div>
</div>
```

</div>
@endsection
