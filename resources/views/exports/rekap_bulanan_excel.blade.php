<table>
    <thead>
        <tr>
            <th>No</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Jabatan</th>
            @foreach ($tanggalList as $tgl)
                <th>{{ $tgl }}</th>
            @endforeach
            <th>Total Akumulasi (HH:MM)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($pegawaiList as $i => $pegawai)
            @php
                $jam = str_pad(floor($pegawai->total_menit / 60), 2, '0', STR_PAD_LEFT);
                $menit = str_pad($pegawai->total_menit % 60, 2, '0', STR_PAD_LEFT);
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $pegawai->nip }}</td>
                <td>{{ $pegawai->nama }}</td>
                <td>{{ $pegawai->jabatan }}</td>
                @foreach ($tanggalList as $tgl)
                    <td>{{ $pegawai->absensi_harian[$tgl] ?? '-' }}</td>
                @endforeach
                <td>{{ $jam }}:{{ $menit }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
