@php
    use Illuminate\Support\Str;   // NEW – utk pemotongan teks
@endphp

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
            <th>Total&nbsp;Akumulasi&nbsp;(HH:MM)</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($pegawaiList as $i => $pegawai)
            @php
                // format total menit → HH:MM
                $jam   = str_pad(intdiv($pegawai->total_menit, 60), 2, '0', STR_PAD_LEFT);
                $menit = str_pad($pegawai->total_menit % 60,      2, '0', STR_PAD_LEFT);
            @endphp

            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $pegawai->nip }}</td>
                <td>{{ $pegawai->nama }}</td>
                <td>{{ $pegawai->jabatan ?? '-' }}</td>

                {{-- kolom per-tanggal --}}
                @foreach ($tanggalList as $tgl)
                    @php
                        $val = $pegawai->absensi_harian[$tgl] ?? '-';
                        // jika string terlalu panjang (libur), potong 25 karakter
                        $display = Str::limit($val, 25, '…');
                    @endphp
                    <td>{{ $display }}</td>
                @endforeach

                <td>{{ $jam }}:{{ $menit }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
