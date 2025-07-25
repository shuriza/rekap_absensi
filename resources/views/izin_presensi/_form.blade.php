{{-- resources/views/izin_presensi/_form.blade.php --}}
{{-- ========================== --}}
{{-- Field tersembunyi: diisi otomatis ketika modal dibuka --}}
{{-- Ganti input tersembunyi ➜ jadi input date yang terlihat --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block mb-2 font-medium text-gray-700">Tanggal Awal</label>
        <input  type="date"  id="izin-awal"  name="tanggal_awal"  required
                class="w-full rounded-lg border-gray-300">
        @error('tanggal_awal') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block mb-2 font-medium text-gray-700">Tanggal Akhir</label>
        <input  type="date"  id="izin-akhir" name="tanggal_akhir"
                class="w-full rounded-lg border-gray-300">
        @error('tanggal_akhir') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

{{-- “Nama Karyawan” tetap tersembunyi --}}
<input type="hidden" id="izin-karyawan" name="karyawan_id">

{{-- ========================== --}}
{{-- Tipe & Jenis Izin --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block mb-2 font-medium text-gray-700">Tipe Izin</label>
        <select name="tipe_ijin" id="tipe-ijin" required class="w-full rounded-lg border-gray-300">
            <option value="">– Pilih tipe –</option>
            @foreach($tipeIjin as $tipe)
                <option value="{{ $tipe }}">{{ $tipe }}</option>
            @endforeach
        </select>
        @error('tipe_ijin') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block mb-2 font-medium text-gray-700">Jenis Izin</label>
        <select name="jenis_ijin" id="jenis-ijin" required class="w-full rounded-lg border-gray-300">
            <option value="">– Pilih jenis –</option>
            @foreach($listJenis as $jenis)
                <option value="{{ $jenis }}">{{ $jenis }}</option>
            @endforeach
        </select>
        @error('jenis_ijin') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

{{-- ========================== --}}
{{-- Lampiran (optional) --}}
<div>
    <label class="block mb-2 font-medium text-gray-700">
        Lampiran (opsional, PDF/JPG/PNG)
    </label>
    <input type="file" name="berkas" accept="application/pdf,image/*"
           class="w-full rounded-lg border-gray-300">
    @error('berkas') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>

{{-- ========================== --}}
{{-- Keterangan --}}
<div>
    <label class="block mb-2 font-medium text-gray-700">Keterangan</label>
    <textarea name="keterangan" rows="3"
              class="w-full rounded-lg border-gray-300"></textarea>
    @error('keterangan') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>

{{-- ========================== --}}
{{-- Tombol aksi --}}
<div class="flex justify-end">
    {{-- Tombol batal: dipakai di modal, dispatch event untuk menutup --}}
 

    <button type="submit"
            class="inline-block px-6 py-2 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
        Simpan
    </button>
</div>
