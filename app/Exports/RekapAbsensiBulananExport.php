<?php

namespace App\Exports;

use App\Models\Karyawan;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;

class RekapAbsensiBulananExport implements FromView
{
    protected $bulan, $tahun;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function view(): View
    {
        $jumlahHari = Carbon::create($this->tahun, $this->bulan)->daysInMonth;
        $tanggalList = range(1, $jumlahHari);

        $pegawaiList = Karyawan::with(['absensi' => function ($query) {
            $query->whereYear('tanggal', $this->tahun)
                  ->whereMonth('tanggal', $this->bulan);
        }])->get();

        foreach ($pegawaiList as $pegawai) {
            $absensiPerTanggal = [];
            $totalMenit = 0;

            foreach ($tanggalList as $tgl) {
                $absensiPerTanggal[$tgl] = '-';
            }

            foreach ($pegawai->absensi as $absen) {
                $day = (int) Carbon::parse($absen->tanggal)->format('d');
                $jamMasuk = $absen->jam_masuk ? Carbon::parse($absen->jam_masuk) : null;
                $jamPulang = $absen->jam_pulang ? Carbon::parse($absen->jam_pulang) : null;

                if ($jamMasuk && $jamPulang && $jamPulang > $jamMasuk) {
                    $totalMenit += $jamMasuk->diffInMinutes($jamPulang);

                    $absensiPerTanggal[$day] = $jamMasuk->format('H:i') . ' - ' . $jamPulang->format('H:i');
                }
            }

            $pegawai->absensi_harian = $absensiPerTanggal;
            $pegawai->total_menit = $totalMenit;
        }

        return view('exports.rekap_bulanan_excel', [
            'pegawaiList' => $pegawaiList,
            'tanggalList' => $tanggalList,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
        ]);
    }
}
