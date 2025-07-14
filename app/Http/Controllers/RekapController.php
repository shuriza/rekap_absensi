<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RekapController extends Controller
{
    /** Default 7 jam 30 menit (450 menit) bila salah satu jam hilang */
    private int $DEFAULT_MINUTES = 7 * 60 + 30;

    /* =========================================================== *
     *  REKAP BULANAN  (tabel segment, total 1-bulan penuh)
     * =========================================================== */
    public function rekap(Request $r)
    {
        /* 1. parameter */
        $bulan   = max(1,  min(12, (int) $r->input('bulan',  date('m'))));
        $tahun   =         (int) $r->input('tahun',  date('Y'));
        $segment = max(1,  min(3,  (int) $r->input('segment', 1)));

        /* 2. tanggal segment */
        $daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;
        [$start,$end] = match ($segment) {
            1       => [1, 10],
            2       => [11, 20],
            default => [21, $daysInMonth],
        };
        $tanggalList = range($start,$end);

        /* 3. query karyawan + presensi & izin satu bulan */
        $pegawaiQuery = Karyawan::with([
            // presensi
            'absensi' => fn($q)=>$q->whereYear('tanggal',$tahun)
                                   ->whereMonth('tanggal',$bulan),

            // izin yg men-sentuh bulan
            'izins'   => fn($q)=>$q->where(function($sub)use($tahun,$bulan){
                $sub->whereYear('tanggal_awal', $tahun)->whereMonth('tanggal_awal', $bulan)
                     ->orWhereYear('tanggal_akhir',$tahun)->whereMonth('tanggal_akhir',$bulan);
            }),
        ]);

        if($r->filled('search')){
            $pegawaiQuery->where('nama','like','%'.$r->search.'%');
        }
        $pegawaiList = $pegawaiQuery->paginate(10)->withQueryString();

        /* helper: time|datetime → Carbon|null */
        $dt = fn(string $tgl, ?string $w)=>
            $w ? (str_contains($w,' ') ? Carbon::parse($w)
                                       : Carbon::parse("$tgl $w"))
               : null;

        /* 4. proses tiap pegawai */
        foreach($pegawaiList as $peg){

            /* 4-a. peta izin: tanggal => kode */
            $mapIzin = [];
            foreach($peg->izins as $iz){
                $range = CarbonPeriod::create(
                    $iz->tanggal_awal,
                    $iz->tanggal_akhir ?? $iz->tanggal_awal
                );
                foreach($range as $d){
                    $mapIzin[$d->toDateString()] = strtok($iz->jenis_ijin,' ');
                }
            }

            /* 4-b. total menit 1-bulan penuh */
            $total = 0;
            foreach($peg->absensi as $row){
                $tglStr = $row->tanggal->toDateString();

                if(isset($mapIzin[$tglStr])) continue; // skip jika ada izin

                $in  = $dt($tglStr,$row->jam_masuk);
                $out = $dt($tglStr,$row->jam_pulang);

                if($in && $out && $out->gt($in)){
                    $total += $in->diffInMinutes($out);        // normal
                } elseif(($in && !$out) || (!$in && $out)){
                    $total += $this->DEFAULT_MINUTES;          // default 7h30m
                }
            }

            /* 4-c. kolom harian utk segment */
            $daily = [];
            $mapPres = $peg->absensi->keyBy(fn($p)=>$p->tanggal->toDateString());

            foreach($tanggalList as $d){
                $tglStr = sprintf('%04d-%02d-%02d',$tahun,$bulan,$d);

                /* izin? */
                if(isset($mapIzin[$tglStr])){
                    $daily[$d] = ['type'=>'izin','label'=>$mapIzin[$tglStr]];
                    continue;
                }

                /* presensi? */
                $row = $mapPres[$tglStr] ?? null;
                if($row){
                    $inTxt  = $dt($tglStr,$row->jam_masuk)?->format('H:i') ?? '-';
                    $outTxt = $dt($tglStr,$row->jam_pulang)?->format('H:i') ?? '-';
                    $daily[$d] = ['type'=>'hadir','label'=>"$inTxt - $outTxt"];
                } else {
                    $daily[$d] = ['type'=>'kosong','label'=>'/'];
                }
            }

            $peg->absensi_harian = $daily;
            $peg->total_menit    = $total;
        }

        return view('absensi.rekap', compact(
            'pegawaiList','tanggalList','bulan','tahun','segment','daysInMonth'
        ));
    }

    /* ===========================================================
    *  REKAP TAHUNAN  (kolom Jan-Des + total)
    * =========================================================== */
    public function rekapTahunan(Request $request)
    {
        $tahun = (int) $request->input('tahun', date('Y'));

        // eager-load presensi setahun
        $pegawaiList = Karyawan::with([
            'absensi' => fn ($q) => $q->whereYear('tanggal', $tahun)
        ])->get();

        // helper parse datetime (menerima string 'time' atau 'datetime')
        $dt = fn (string $tgl, ?string $w) =>
            $w
                ? (str_contains($w, ' ')
                    ? Carbon::parse($w)          // sudah YYYY-MM-DD HH:ii:ss
                    : Carbon::parse("$tgl $w"))  // gabung tanggal + jam
                : null;

        foreach ($pegawaiList as $pegawai) {

            // menit per bulan 1-12
            $menitPerBulan = array_fill(1, 12, 0);

            foreach ($pegawai->absensi as $row) {
                // pastikan bulan 1-12 selalu numeric
                $tglStr   = $row->tanggal instanceof Carbon
                            ? $row->tanggal->toDateString()
                            : $row->tanggal;                 // string di DB
                $bulanIdx = Carbon::parse($tglStr)->month;   // 1 … 12

                $in  = $dt($tglStr, $row->jam_masuk);
                $out = $dt($tglStr, $row->jam_pulang);

                if ($in && $out && $out->gt($in)) {
                    // presensi lengkap
                    $menitPerBulan[$bulanIdx] += $in->diffInMinutes($out);
                } elseif (($in && !$out) || (!$in && $out)) {
                    // hanya masuk atau hanya pulang → +7h30m
                    $menitPerBulan[$bulanIdx] += $this->DEFAULT_MINUTES;
                }
            }

            /* format ke HH:MM */
            $fmt = fn (int $m) =>
                str_pad(intval($m / 60), 2, '0', STR_PAD_LEFT) . ':' .
                str_pad($m % 60,        2, '0', STR_PAD_LEFT);

            $pegawai->rekap_tahunan = array_map($fmt, $menitPerBulan);
            $pegawai->total_tahun   = $fmt(array_sum($menitPerBulan));
        }

        return view('absensi.rekap-tahunan', compact('pegawaiList', 'tahun'));
    }

}
