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
          <td>{{ $karyawan->rekap_tahunan[$bulan] ?? '00:00' }}</td>
        @endfor
        <td>{{ $karyawan->total_fmt ?? '00 hari 00 jam 00 menit' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>