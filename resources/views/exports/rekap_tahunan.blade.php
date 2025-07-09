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
                        $jamBulan = $karyawan->absensi->filter(function($a) use ($bulan) {
                            return \Carbon\Carbon::parse($a->tanggal)->month == $bulan;
                        })->sum(function($a) {
                            return $a->jam_masuk && $a->jam_pulang
                                ? \Carbon\Carbon::parse($a->jam_pulang)->diffInMinutes(\Carbon\Carbon::parse($a->jam_masuk))
                                : 0;
                        });
                        $jam = str_pad(floor($jamBulan / 60), 2, '0', STR_PAD_LEFT);
                        $menit = str_pad($jamBulan % 60, 2, '0', STR_PAD_LEFT);
                    @endphp
                    <td>{{ $jam }}:{{ $menit }}</td>
                @endfor
                <td>
                    @php
                        $totalJam = str_pad(floor($karyawan->total_menit / 60), 2, '0', STR_PAD_LEFT);
                        $totalMenit = str_pad($karyawan->total_menit % 60, 2, '0', STR_PAD_LEFT);
                    @endphp
                    {{ $totalJam }}:{{ $totalMenit }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
