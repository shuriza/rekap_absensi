@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
  <h2 class="text-2xl font-semibold mb-6">Detail Izin Presensi</h2>

  <div class="bg-white rounded-xl shadow p-6 space-y-6">
    <div class="grid grid-cols-3 gap-2 text-sm">
      <span class="font-medium">Nama</span>
      <span class="col-span-2">{{ $izinPresensi->karyawan->nama }}</span>

      <span class="font-medium">Tipe</span>
      <span class="col-span-2">{{ $izinPresensi->tipe_ijin }}</span>

      <span class="font-medium">Periode</span>
      <span class="col-span-2">
        {{ $izinPresensi->tanggal_awal->format('d-m-Y') }}
        @if($izinPresensi->tanggal_akhir)
          – {{ $izinPresensi->tanggal_akhir->format('d-m-Y') }}
        @endif
      </span>

      <span class="font-medium">Jenis</span>
      <span class="col-span-2">{{ $izinPresensi->jenis_ijin }}</span>

      <span class="font-medium">Keterangan</span>
      <span class="col-span-2">{{ $izinPresensi->keterangan ?: '-' }}</span>
    </div>

    @if($izinPresensi->berkas)
      @php $url = Storage::url($izinPresensi->berkas); @endphp
      <div class="border rounded-lg overflow-hidden">
        @if(Str::endsWith($izinPresensi->berkas, ['.jpg','.jpeg','.png','.gif']))
          <img src="{{ $url }}" class="w-full" alt="Lampiran">
        @else
          <iframe src="{{ $url }}" class="w-full h-96"></iframe>
        @endif
      </div>
    @endif

    <div class="flex justify-end space-x-3">
      <a href="{{ route('izin_presensi.index') }}"
         class="px-4 py-2 rounded-lg border hover:bg-gray-50">Kembali</a>
      <form action="{{ route('izin_presensi.destroy', $izinPresensi) }}" method="POST"
            onsubmit="return confirm('Hapus izin ini?');">
        @csrf @method('DELETE')
        <button type="submit"
                class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">Hapus</button>
      </form>
    </div>
  </div>
</div>
@endsection