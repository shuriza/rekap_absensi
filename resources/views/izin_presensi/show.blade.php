@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col px-6 py-4">
    {{-- Navigation Tabs --}}
    <div class="my-8">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <a href="{{ route('izin_presensi.index') }}"
                    class="group inline-flex items-center py-2 px-1 border-b-2 border-blue-500 text-blue-600 font-medium text-sm">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Kembali ke Daftar Izin
                </a>
            </nav>
        </div>
    </div>

    {{-- Header --}}
    <h1 class="text-lg font-semibold mb-4">
        Detail Izin Presensi - {{ $izinPresensi->karyawan->nama }}
    </h1>

    {{-- Content Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {{-- Card Header --}}
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Informasi Detail Izin
                </h3>
                <div class="flex items-center space-x-2 text-sm text-gray-500">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $izinPresensi->tipe_ijin }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ $izinPresensi->tanggal_awal->translatedFormat('d M Y') }}
                        @if($izinPresensi->tanggal_akhir && $izinPresensi->tanggal_akhir != $izinPresensi->tanggal_awal)
                            - {{ $izinPresensi->tanggal_akhir->translatedFormat('d M Y') }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Card Content --}}
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Detail Informasi --}}
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-4">
                        {{-- Nama Karyawan --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Nama Karyawan
                            </label>
                            <div class="font-medium text-gray-900">{{ $izinPresensi->karyawan->nama }}</div>
                            <div class="text-sm text-gray-600">{{ $izinPresensi->karyawan->departemen }}</div>
                        </div>

                        {{-- Tipe Izin --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a1.994 1.994 0 01-1.414.586H7a4 4 0 01-4-4V7a4 4 0 014-4z"></path>
                                </svg>
                                Tipe Izin
                            </label>
                            <div class="font-medium text-gray-900">{{ $izinPresensi->tipe_ijin }}</div>
                        </div>

                        {{-- Periode --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Periode Izin
                            </label>
                            <div class="font-medium text-gray-900">
                                {{ $izinPresensi->tanggal_awal->translatedFormat('d F Y') }}
                                @if($izinPresensi->tanggal_akhir && $izinPresensi->tanggal_akhir != $izinPresensi->tanggal_awal)
                                    <span class="text-gray-500 mx-2">sampai</span>
                                    {{ $izinPresensi->tanggal_akhir->translatedFormat('d F Y') }}
                                @endif
                            </div>
                            @if($izinPresensi->tanggal_akhir && $izinPresensi->tanggal_akhir != $izinPresensi->tanggal_awal)
                                @php
                                    $durasi = $izinPresensi->tanggal_awal->diffInDays($izinPresensi->tanggal_akhir) + 1;
                                @endphp
                                <div class="text-sm text-gray-600 mt-1">Durasi: {{ $durasi }} hari</div>
                            @else
                                <div class="text-sm text-gray-600 mt-1">Durasi: 1 hari</div>
                            @endif
                        </div>

                        {{-- Jenis Izin --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 7a2 2 0 00-2 2v2m0 0V9a2 2 0 012-2h14a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                Jenis Izin
                            </label>
                            <div class="font-medium text-gray-900">{{ $izinPresensi->jenis_ijin }}</div>
                        </div>

                        {{-- Keterangan --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                Keterangan
                            </label>
                            <div class="text-gray-900 whitespace-pre-line">
                                {{ $izinPresensi->keterangan ?: 'Tidak ada keterangan khusus' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Lampiran --}}
                <div class="space-y-6">
                    @if($izinPresensi->berkas)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                                Lampiran Berkas
                            </label>
                            <div class="border rounded-lg overflow-hidden bg-white">
                                @php
                                    $url = route('izin_presensi.lampiran', $izinPresensi);
                                    $isImage = Str::endsWith($izinPresensi->berkas, ['jpg','jpeg','png','gif','webp']);
                                @endphp

                                @if($isImage)
                                    {{-- Foto langsung tampil --}}
                                    <img src="{{ $url }}"
                                        alt="Lampiran izin {{ $izinPresensi->karyawan->nama }}"
                                        class="w-full max-h-[500px] object-contain bg-gray-50">
                                @else
                                    {{-- PDF dll di-embed iframe --}}
                                    <iframe src="{{ $url }}"
                                            class="w-full h-96 bg-gray-50"
                                            title="Lampiran izin {{ $izinPresensi->karyawan->nama }}"></iframe>
                                @endif
                                
                                {{-- Link download --}}
                                <div class="p-3 bg-gray-50 border-t">
                                    <a href="{{ $url }}" target="_blank" 
                                       class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Buka lampiran di tab baru
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                                Lampiran Berkas
                            </label>
                            <div class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm">Tidak ada lampiran berkas</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-xs text-gray-500">
                    <span>Dibuat: {{ $izinPresensi->created_at->translatedFormat('d F Y, H:i') }}</span>
                    @if($izinPresensi->updated_at != $izinPresensi->created_at)
                        <span class="ml-4">Diperbarui: {{ $izinPresensi->updated_at->translatedFormat('d F Y, H:i') }}</span>
                    @endif
                </div>
                
                <div class="flex space-x-3">
                    <a href="{{ route('izin_presensi.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </a>
                    
                    <button onclick="openDeleteModal()" type="button"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus Izin
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="text-center py-4 text-sm text-gray-600 mt-8">
        Dinas Penanaman Modal &amp; Pelayanan Terpadu Satu Pintu &middot;
        Detail Izin Presensi {{ $izinPresensi->karyawan->nama }}
    </footer>
</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-60">
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
                <button onclick="closeDeleteModal()" type="button"
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
                    Apakah Anda yakin ingin menghapus izin untuk <strong>{{ $izinPresensi->karyawan->nama }}</strong>? 
                    Tindakan ini tidak dapat dibatalkan.
                </p>

                {{-- Action Buttons --}}
                <div class="flex justify-center space-x-3">
                    <button onclick="closeDeleteModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Batal
                    </button>
                    <form action="{{ route('izin_presensi.destroy', $izinPresensi) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                            Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openDeleteModal() {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.body.classList.add('overflow-y-hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.body.classList.remove('overflow-y-hidden');
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
</script>
@endpush
@endsection
