@extends('layouts.app')

@section('content')
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Daftar Izin Presensi</h4>
        <a href="{{ route('izin_presensi.create') }}" class="btn btn-success">+ Baru</a>
      </div>

      <div class="card shadow-sm rounded">
        <div class="card-body p-0">
          <div class="table-responsive">
            {{-- Tambahkan kelas table-fixed dan colgroup --}}
            <table class="table table-hover table-bordered table-fixed align-middle mb-0">
              <colgroup>
                <col style="width:5%">
                <col style="width:20%">
                <col style="width:10%">
                <col style="width:15%">
                <col style="width:15%">
                <col style="width:10%">
                <col style="width:15%">
                <col style="width:10%">
              </colgroup>
              <thead class="table-light text-center">
                <tr>
                  <th>#</th>
                  <th>Karyawan</th>
                  <th>Tipe</th>
                  <th>Periode</th>
                  <th>Jenis</th>
                  <th>Berkas</th>
                  <th>Keterangan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($data as $i => $izin)
                <tr>
                  <td class="text-center">{{ $data->firstItem() + $i }}</td>
                  {{-- Nama/NIP rata kiri --}}
                  <td class="text-start">
                    {{ $izin->karyawan->nama }}<br>
                    <small>{{ $izin->karyawan->nip }}</small>
                  </td>
                  <td class="text-center">{{ $izin->tipe_ijin }}</td>
                  <td class="text-center">
                    {{ $izin->tanggal_awal->format('d-m-Y') }}
                    @if($izin->tanggal_akhir)–{{ $izin->tanggal_akhir->format('d-m-Y') }}@endif
                  </td>
                  <td class="text-center">{{ $izin->jenis_ijin }}</td>
                  <td class="text-center">
                    @if($izin->berkas)
                      <a href="{{ Storage::url($izin->berkas) }}" target="_blank">Lihat</a>
                    @else
                      -
                    @endif
                  </td>
                  <td class="text-center">{{ $izin->keterangan ?? '-' }}</td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger" disabled>Hapus</button>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="8" class="text-center py-4">Belum ada data.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer text-center">
          {{ $data->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

{{-- CSS khusus untuk table-fixed --}}
@push('styles')
<style>
.table-fixed {
  table-layout: fixed;
  width: 100%;
}
.table-fixed th,
.table-fixed td {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
@endpush
@endsection
