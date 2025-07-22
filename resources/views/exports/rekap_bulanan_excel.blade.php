@php
    use Illuminate\Support\Str;   // cukup sekali di bagian awal view
@endphp

<table class="min-w-full border-collapse text-sm text-center">
    {{-- ================= T H E A D ================= --}}
    <thead class="bg-gray-800 text-white">
        <tr>
            <th class="border px-2 py-2">No</th>
            <th class="border px-2 py-2">Nama</th>

            @foreach ($tanggalList as $tgl)
                <th class="border px-2 py-2">{{ $tgl }}</th>
            @endforeach

            <th class="border px-2 py-2">Total Akumulasi<br>(HH:MM)</th>
        </tr>
    </thead>

    {{-- ================= T B O D Y ================= --}}
    <tbody class="bg-white text-gray-800">

    @foreach ($pegawaiList as $loopIdx => $pegawai)
        {{-- ───── total menit → HH:MM ───── --}}
        @php
            $jam   = str_pad(intdiv($pegawai->total_menit, 60), 2, '0', STR_PAD_LEFT);
            $menit = str_pad($pegawai->total_menit % 60,      2, '0', STR_PAD_LEFT);
        @endphp

        <tr class="hover:bg-gray-50">
            <td class="border px-2 py-1">{{ $loopIdx + 1 }}</td>
            <td class="border px-2 py-1 text-left">{{ $pegawai->nama }}</td>

            {{-- ======= K O L O M   T A N G G A L ======= --}}
            @foreach ($tanggalList as $tgl)
                @php
                    /* ----------------------------------------------------
                     * $info = ['type' => ..., 'label' => ...]
                     * fallback jika key tidak ada / format masih string
                     * -------------------------------------------------- */
                    $raw = $pegawai->absensi_harian[$tgl] ?? '-';
                    $info = is_array($raw) ? $raw : ['type' => 'kosong', 'label' => $raw];

                    /* warna latar */
                    $bg = match ($info['type']) {
                        'kosong'    => 'bg-red-500',     // tidak absen
                        'terlambat' => 'bg-yellow-200',
                        'izin'      => 'bg-blue-300',
                        'libur'     => 'bg-gray-300',
                        default     => '',                // hadir normal
                    };

                    /* warna teks */
                    $txt = $bg === 'bg-red-500' ? 'text-white' : 'text-black';

                    /* tampilan label (max 25 char jika string) */
                    $label = is_string($info['label'])
                                ? Str::limit($info['label'], 25, '…')
                                : $info['label'];
                @endphp

                <td class="border px-1 py-1 {{ $bg }} {{ $txt }}">
                    {{ $label }}
                </td>
            @endforeach

            {{-- total akumulasi --}}
            <td class="border px-2 py-1 font-semibold">{{ $jam }}:{{ $menit }}</td>
        </tr>
    @endforeach

    </tbody>
</table>
