<?php
namespace App\Exports;

use App\Models\Karyawan;
use App\Models\Absensi;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;

class RekapAbsensiTahunanExport implements FromView
{
    protected $tahun;

    public function __construct($tahun)
    {
        $this->tahun = $tahun;
    }

    public function view(): View
    {
        $karyawans = Karyawan::with(['absensi' => function ($query) {
            $query->whereYear('tanggal', $this->tahun);
        }])->get();

        // Siapkan tanggal untuk setiap bulan
        $tanggalPerBulan = [];
        for ($month = 1; $month <= 12; $month++) {
            $tanggalPerBulan[$month] = range(1, Carbon::create($this->tahun, $month)->daysInMonth);
        }

        // Hitung total menit per karyawan
        foreach ($karyawans as $pegawai) {
            $totalMenit = 0;
            foreach ($pegawai->absensi as $absen) {
                if ($absen->jam_masuk && $absen->jam_pulang) {
                    $start = Carbon::parse($absen->jam_masuk);
                    $end = Carbon::parse($absen->jam_pulang);
                    $totalMenit += $end->diffInMinutes($start);
                }
            }
            $pegawai->total_menit = $totalMenit;
        }

        return view('exports.rekap_tahunan', [
            'karyawans' => $karyawans,
            'tahun' => $this->tahun,
            'tanggalPerBulan' => $tanggalPerBulan,
        ]);
    }
}
