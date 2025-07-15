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
            $jamBulan = $karyawan->absensi
              ->filter(fn ($a) => \Carbon\Carbon::parse($a->tanggal)->month == $bulan)
              ->sum(function ($a) {
                  $hasMasuk  = !empty($a->jam_masuk);
                  $hasPulang = !empty($a->jam_pulang);

                  // --- Kondisi lengkap: hitung selisih aktual ---
                  if ($hasMasuk && $hasPulang) {
                      $masuk  = \Carbon\Carbon::parse($a->jam_masuk);
                      $pulang = \Carbon\Carbon::parse($a->jam_pulang);

                      // Shift malam: pulang ≤ masuk → tambah 1 hari
                      if ($pulang->lessThanOrEqualTo($masuk)) {
                          $pulang->addDay();
                      }

                      $selisih = $masuk->diffInMinutes($pulang, false);
                      return ($selisih > 0 && $selisih <= 1440) ? $selisih : 0; // validasi 0–24 jam
                  }

                  // --- Salah satu jam kosong: fallback 7 j 30 m = 450 menit ---
                  if ($hasMasuk || $hasPulang) {
                      return 450;
                  }

                  return 0;
              });

            // Format HH:MM
            $jam   = str_pad(intval($jamBulan / 60), 2, '0', STR_PAD_LEFT);
            $menit = str_pad($jamBulan % 60,       2, '0', STR_PAD_LEFT);
          @endphp
          <td>{{ $jam }}:{{ $menit }}</td>
        @endfor

        <td>
          @php
            $totalJam   = str_pad(floor($karyawan->total_menit / 60), 2, '0', STR_PAD_LEFT);
            $totalMenit = str_pad($karyawan->total_menit % 60,      2, '0', STR_PAD_LEFT);
          @endphp
          {{ $totalJam }}:{{ $totalMenit }}
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
