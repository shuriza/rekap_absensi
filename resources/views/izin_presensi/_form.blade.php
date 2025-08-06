{{-- =================  field tersembunyi  ================= --}}
<input type="hidden" id="izin-karyawan" name="karyawan_id">

{{-- =================  periode tanggal  ================= --}}
<div class="grid sm:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm">Tanggal Awal</label>
    <input id="izin-awal" type="date" name="tanggal_awal"
           required class="w-full border rounded">
  </div>
  <div>
    <label class="block text-sm">Tanggal Akhir</label>
    <input id="izin-akhir" type="date" name="tanggal_akhir"
           class="w-full border rounded">
  </div>
</div>

{{-- =================  tipe & jenis  ================= --}}
<div class="grid sm:grid-cols-2 gap-4 mt-4">
  
  <div>
    <label class="block text-sm">Jenis Izin</label>
    <select id="jenis-ijin" name="jenis_ijin" required class="w-full border rounded">
      <option value="">– pilih –</option>
      @foreach($listJenis as $j)<option>{{ $j }}</option>@endforeach
    </select>
  </div>
  <div>
    <label class="block text-sm">Tipe Izin</label>
    <select id="tipe-ijin" name="tipe_ijin" required class="w-full border rounded">
      <option value="">– pilih –</option>
      @foreach($tipeIjin as $t)<option>{{ $t }}</option>@endforeach
    </select>
  </div>
</div>

<div class="mt-4">
  <label class="block text-sm">Keterangan</label>
  <textarea id="keterangan-izin" name="keterangan" rows="3"
            class="w-full border rounded"></textarea>
</div>

{{-- preview file lama --}}
<div id="preview-lampiran" class="text-sm text-blue-600 mt-2"></div>

<div class="mt-4">
  <label class="block text-sm">Lampiran</label>
  <input type="file" name="berkas" class="w-full border rounded">
</div>

{{-- =================  tombol aksi  ================= --}}
<div class="flex justify-end gap-3 pt-4">
  <button id="btn-hapus" type="button"
          class="hidden px-4 py-2 bg-rose-500 text-white rounded
                 hover:bg-rose-700 transition"
          onclick="showDeleteConfirm(this)">
      Hapus
  </button>

  <button id="btn-simpan" type="submit"
          class="px-6 py-2 bg-emerald-600 text-white rounded">
      Simpan
  </button>
</div>
