@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.4/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
@endpush

@section('content')
<div class="container mx-auto px-4 py-8 max-w-xl">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Buat Izin Presensi</h2>

    <form action="{{ route('izin_presensi.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 bg-white p-6 rounded-xl shadow">
        @csrf

        <!-- Karyawan -->
        <div>
            <label class="block mb-2 font-medium text-gray-700">Nama Karyawan</label>
            <select id="karyawan_id" name="karyawan_id" required class="tom-select w-full rounded-lg border-gray-300">
                <option value="">– Pilih karyawan –</option>
                @foreach($karyawans as $karyawan)
                    <option value="{{ $karyawan->id }}" {{ old('karyawan_id') == $karyawan->id ? 'selected' : '' }}>
                        {{ $karyawan->nama }}
                    </option>
                @endforeach
            </select>
            @error('karyawan_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Tipe & Jenis Izin -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <div>
                <label class="block mb-2 font-medium text-gray-700">Jenis Izin</label>
                <select name="jenis_ijin" required class="w-full rounded-lg border-gray-300">
                    <option value="">– Pilih jenis –</option>
                    @foreach($listJenis as $jenis)
                        <option value="{{ $jenis }}" {{ old('jenis_ijin') == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                    @endforeach
                </select>
                @error('jenis_ijin') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block mb-2 font-medium text-gray-700">Tipe Izin</label>
                <select name="tipe_ijin" required class="w-full rounded-lg border-gray-300">
                    <option value="">– Pilih tipe –</option>
                    @foreach($tipeIjin as $tipe)
                        <option value="{{ $tipe }}" {{ old('tipe_ijin') == $tipe ? 'selected' : '' }}>{{ $tipe }}</option>
                    @endforeach
                </select>
                @error('tipe_ijin') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Periode -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block mb-2 font-medium text-gray-700">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" value="{{ old('tanggal_awal') }}" required class="w-full rounded-lg border-gray-300">
                @error('tanggal_awal') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block mb-2 font-medium text-gray-700">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" value="{{ old('tanggal_akhir') }}" class="w-full rounded-lg border-gray-300">
                @error('tanggal_akhir') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Berkas -->
        <div>
            <label class="block mb-2 font-medium text-gray-700">Lampiran (opsional, PDF/JPG/PNG)</label>
            <input type="file" name="berkas" accept="application/pdf,image/*" class="w-full rounded-lg border-gray-300">
            @error('berkas') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Keterangan -->
        <div>
            <label class="block mb-2 font-medium text-gray-700">Keterangan</label>
            <textarea name="keterangan" rows="3" class="w-full rounded-lg border-gray-300">{{ old('keterangan') }}</textarea>
            @error('keterangan') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Tombol -->
        <div class="flex justify-end">
            <a href="{{ route('izin_presensi.index') }}" class="inline-block px-4 py-2 mr-3 rounded-lg border text-gray-700 hover:bg-gray-50 transition">Batal</a>
            <button type="submit" class="inline-block px-6 py-2 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script>
        new TomSelect('#karyawan_id', {
            create: false,
            sortField: {field: 'text'}
        });
    </script>
@endpush
