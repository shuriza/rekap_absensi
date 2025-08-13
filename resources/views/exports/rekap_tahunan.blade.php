<table>
  <thead>
    <tr>
      <th>No</th>
      <th>Nama</th>
      @for ($bulan = 1; $bulan <= 12; $bulan++)
        <th>{{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }}</th>
      @endfor
      <th>Total Akumulasi</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($karyawans as $index => $karyawan)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $karyawan->nama }}</td>
        @for ($bulan = 1; $bulan <= 12; $bulan++)
          @php
            // Format konsisten dengan bulanan: "X hari Y jam Z menit" (basis 24 jam/hari)
            $menitBulan = $karyawan->menitPerBulan[$bulan] ?? 0;
            if ($menitBulan > 0) {
              $hari = intdiv($menitBulan, 1440);  // 1440 menit = 24 jam = 1 hari kalender
              $sisa = $menitBulan % 1440;
              $jam = str_pad(intdiv($sisa, 60), 2, '0', STR_PAD_LEFT);
              $mnt = str_pad($sisa % 60, 2, '0', STR_PAD_LEFT);
              $formatBulan = "{$hari} hari {$jam} jam {$mnt} menit";
            } else {
              $formatBulan = '—';
            }
          @endphp
          <td>{{ $formatBulan }}</td>
        @endfor
        <td>{{ $karyawan->total_fmt ?? '00 hari 00 jam 00 menit' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>