<?php

namespace App\Exports;

use App\Models\Karyawan;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;

class RekapAbsensiBulananExport implements FromView
{
    private int $DEFAULT_MINUTES = 7 * 60 + 30;   // 450 menit

    public function __construct(private int $bulan, private int $tahun) {}

    /** helper: time|datetime → Carbon|null */
    private function dt(string $tgl, ?string $w): ?Carbon
    {
        return $w
            ? (str_contains($w, ' ') ? Carbon::parse($w)
                                     : Carbon::parse("$tgl $w"))
            : null;
    }

    public function view(): View
    {
        $jumlahHari  = Carbon::create($this->tahun, $this->bulan)->daysInMonth;
        $tanggalList = range(1, $jumlahHari);

        $pegawaiList = Karyawan::with(['absensi'=>fn($q)=>
            $q->whereYear('tanggal', $this->tahun)
              ->whereMonth('tanggal',$this->bulan)
        ])->get();

        foreach ($pegawaiList as $peg) {

            /* inisialisasi */
            $harian = array_fill_keys($tanggalList, '-');
            $total  = 0;

            foreach ($peg->absensi as $row) {
                $tglStr = $row->tanggal->toDateString();
                $d      = (int) Carbon::parse($tglStr)->day;

                $in  = $this->dt($tglStr, $row->jam_masuk);
                $out = $this->dt($tglStr, $row->jam_pulang);

                /* --- hitung menit + label -------------------------------- */
                if ($in && $out && $out->gt($in)) {
                    // presensi lengkap
                    $total        += $in->diffInMinutes($out);
                    $harian[$d]    = $in->format('H:i').' - '.$out->format('H:i');
                }
                elseif ($in && !$out) {
                    // hanya jam datang
                    $total        += $this->DEFAULT_MINUTES;
                    $harian[$d]    = $in->format('H:i').' - -';
                }
                elseif (!$in && $out) {
                    // hanya jam pulang
                    $total        += $this->DEFAULT_MINUTES;
                    $harian[$d]    = '- - '.$out->format('H:i');
                }
            }

            $peg->absensi_harian = $harian;
            $peg->total_menit    = $total;
        }

        return view('exports.rekap_bulanan_excel', [
            'pegawaiList' => $pegawaiList,
            'tanggalList' => $tanggalList,
            'bulan'       => $this->bulan,
            'tahun'       => $this->tahun,
        ]);
    }
}
