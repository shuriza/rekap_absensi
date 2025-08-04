@php
    use Illuminate\Support\Str;
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
            <th class="border px-2 py-2">Total Akumulasi<br>(Hari Jam Menit)</th>
        </tr>
    </thead>

    {{-- ================= T B O D Y ================= --}}
    <tbody class="bg-white text-gray-800">
        @foreach ($pegawaiList as $loopIdx => $pegawai)
            @php
                // Konversi total menit ke hari, jam, menit
                $totalMinutes = $pegawai->total_menit ?? 0;
                $totalDays = floor($totalMinutes / (60 * 24));
                $remainingMinutes = $totalMinutes % (60 * 24);
                $totalHours = floor($remainingMinutes / 60);
                $totalMinutes = $remainingMinutes % 60;
                $totalAkumulasi = sprintf('%d hari %02d jam %02d menit', $totalDays, $totalHours, $totalMinutes);
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
                        $raw = $pegawai->absensi_harian[$tgl] ?? ['type' => 'kosong', 'label' => '-'];
                        $info = is_array($raw) ? $raw : ['type' => 'kosong', 'label' => $raw];

                        /* warna latar berdasarkan tipe (sinkronisasi dengan Excel) */
                        $bg = match ($info['type']) {
                            'kosong'    => 'bg-red-500',     // Merah (#FF5252)
                            'terlambat' => 'bg-yellow-300',  // Kuning (#FFF59D)
                            'izin'      => 'bg-blue-300',    // Biru (#90CAF9)
                            'libur'     => 'bg-gray-300',    // Abu-abu (#E0E0E0)
                            'hadir'     => '',               // Tanpa warna (default)
                            default     => 'bg-gray-200',    // Fallback untuk tipe tak dikenal
                        };

                        /* warna teks */
                        $txt = $bg === 'bg-red-500' ? 'text-white' : 'text-black';

                        /* tampilan label (max 25 char jika string) */
                        $label = is_string($info['label']) ? Str::limit($info['label'], 25, '…') : $info['label'];

                        // Debugging sementara (hapus setelah selesai)
                        // dd($info);
                    @endphp

                    <td class="border px-1 py-1 {{ $bg }} {{ $txt }}">
                        {{ $label }}
                    </td>
                @endforeach

                {{-- total akumulasi --}}
                <td class="border px-2 py-1 font-semibold">{{ $totalAkumulasi }}</td>
            </tr>
        @endforeach

        <!-- Tambahkan baris total akumulasi sebulan (opsional) -->
        @if (isset($totalAkumulasiSemua))
            <tr class="bg-gray-200 font-bold">
                <td class="border px-2 py-1" colspan="{{ count($tanggalList) + 2 }}">Total Akumulasi Sebulan</td>
                <td class="border px-2 py-1">{{ $totalAkumulasiSemua }}</td>
            </tr>
        @endif
    </tbody>
</table>
