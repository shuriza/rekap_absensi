<table>
    <thead>
        <tr>
            <th>No</th><th>NIP</th><th>Nama</th><th>Tipe Izin</th><th>Tgl Awal</th><th>Tgl Akhir</th><th>Jenis</th><th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach ($karyawans as $kar)
            @foreach ($kar->izins as $iz)
                <tr>
                    <td>{{ $no++ }}</td>
                    <td>{{ $kar->nip }}</td>
                    <td>{{ $kar->nama }}</td>
                    <td>{{ $iz->tipe_ijin }}</td>
                    <td>{{ $iz->tanggal_awal->format('d-m-Y') }}</td>
                    <td>{{ $iz->tanggal_akhir? $iz->tanggal_akhir->format('d-m-Y'):'-' }}</td>
                    <td>{{ $iz->jenis_ijin }}</td>
                    <td>{{ $iz->keterangan }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>