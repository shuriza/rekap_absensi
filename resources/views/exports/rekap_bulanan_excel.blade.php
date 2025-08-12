@php
    use Illuminate\Support\Str;
@endphp

<div class="mb-4 text-center bg-gray-800 text-white py-2 rounded-t">
    <h2 class="text-xl font-bold">Rekap Absensi Bulan {{ $namaBulan }} {{ $tahun }}</h2>
</div>

<table class="min-w-full border-collapse text-sm text-center">
    <thead class="bg-gray-800 text-white">
        <tr>
            <th class="border px-2 py-2">No</th>
            <th class="border px-2 py-2">Nama</th>
            @foreach ($tanggalList as $tgl)
                <th class="border px-2 py-2">{{ $tgl }}</th>
            @endforeach
            <th class="border px-2 py-2">Total Akumulasi<br>(Hari Jam Menit)</th>
        </tr>
    </thead>

    <tbody class="bg-white text-gray-800">
        @foreach ($pegawaiList as $i => $pegawai)
            @php
                // === Format total bulan pakai basis 7j30m/hari ===
                $perHari = 450;
                $total   = (int) ($pegawai->total_menit ?? 0);
                $hari    = intdiv($total, $perHari);
                $sisa    = $total % $perHari;
                $jam     = intdiv($sisa, 60);
                $menit   = $sisa % 60;
                $totalFmt = sprintf('%d hari %02d jam %02d menit', $hari, $jam, $menit);
            @endphp

            <tr class="hover:bg-gray-50">
                <td class="border px-2 py-1">{{ $i + 1 }}</td>
                <td class="border px-2 py-1 text-left">{{ $pegawai->nama }}</td>

                {{-- Kolom tanggal --}}
                @foreach ($tanggalList as $tgl)
                    @php
                        $raw  = $pegawai->absensi_harian[$tgl] ?? ['type' => 'kosong', 'label' => '-'];
                        $info = is_array($raw) ? $raw : ['type' => 'kosong', 'label' => (string)$raw];

                        // Warna konsisten dengan web/Excel
                        $bg = match ($info['type']) {
                            'kosong'       => 'bg-red-500',
                            'tidak_valid'  => 'bg-red-500',
                            'terlambat'    => 'bg-yellow-300',
                            'izin'         => 'bg-blue-300',
                            'libur'        => 'bg-gray-300',
                            'hadir'        => '',
                            default        => 'bg-gray-200',
                        };
                        $txt   = $bg === 'bg-red-500' ? 'text-white' : 'text-black';
                        $label = is_string($info['label']) ? Str::limit($info['label'], 25, '…') : $info['label'];
                    @endphp

                    <td class="border px-1 py-1 {{ $bg }} {{ $txt }}">
                        {{ $label }}
                    </td>
                @endforeach

                <td class="border px-2 py-1 font-semibold">{{ $totalFmt }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
