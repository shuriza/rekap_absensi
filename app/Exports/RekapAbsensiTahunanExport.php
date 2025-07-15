<?php
namespace App\Exports;

use App\Models\Karyawan;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;

class RekapAbsensiTahunanExport implements FromView
{
    protected int $tahun;

    public function __construct(int $tahun)
    {
        $this->tahun = $tahun;
    }

    public function view(): View
    {
        // Ambil karyawan + absensi untuk tahun yang diminta
        $karyawans = Karyawan::with([
            'absensi' => function ($q) {
                $q->whereYear('tanggal', $this->tahun);
            },
        ])->get();

        foreach ($karyawans as $pegawai) {
            // Inisialisasi rekap 12 bulan & total
            $rekapBulanan = array_fill(1, 12, 0);
            $totalMenit   = 0;

            foreach ($pegawai->absensi as $absen) {
                // Skip record jika data tidak lengkap
                if (!$absen->jam_masuk || !$absen->jam_pulang) {
                    continue;
                }

                $masuk  = Carbon::parse($absen->jam_masuk);
                $pulang = Carbon::parse($absen->jam_pulang);

                // Toleransi shift malam: kalau pulang <= masuk, anggap pulang esok hari
                if ($pulang->lessThanOrEqualTo($masuk)) {
                    $pulang->addDay();
                }

                // Selisih dalam menit (absolute = false untuk deteksi negatif)
                $selisih = $masuk->diffInMinutes($pulang, false);

                // Skip bila data anomali (negatif atau > 24 jam)
                if ($selisih <= 0 || $selisih > 24 * 60) {
                    continue;
                }

                $bulan = (int) Carbon::parse($absen->tanggal)->month;

                $rekapBulanan[$bulan] += $selisih;
                $totalMenit           += $selisih;
            }

            $pegawai->rekap_tahunan = $rekapBulanan;
            $pegawai->total_menit   = $totalMenit;
        }

        return view('exports.rekap_tahunan', [
            'karyawans' => $karyawans,
            'tahun'     => $this->tahun,
        ]);
    }
}