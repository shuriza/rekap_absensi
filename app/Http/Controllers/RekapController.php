<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Karyawan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RekapController extends Controller
{
    /** 7 jam 30 menit (450 menit) bila jam masuk / pulang tidak lengkap */
    private int $defaultMinutes = 7 * 60 + 30;

    /* ==========================================================
     *  R E K A P   B U L A N A N
     * ========================================================== */
    public function rekap(Request $r)
    {
        /* 1️⃣  Parameter dasar ------------------------------------------------ */
        $bulan   = max(1,  min(12, (int) $r->input('bulan',  date('m'))));
        $tahun   =         (int) $r->input('tahun',  date('Y'));
        $segment = max(1,  min(3,  (int) $r->input('segment', 1)));
        $sort    = $r->input('sort');   // '', nama_asc, nama_desc, total_asc, total_desc

        /* Rentang tanggal untuk segment */
        $daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;
        [$start,$end] = match ($segment) {
            1 => [1,10], 2 => [11,20], default => [21,$daysInMonth],
        };
        $tanggalList = range($start,$end);

        /* 2️⃣  Libur nasional / manual --------------------------------------- */
        $holidayMap = Holiday::whereYear('tanggal',  $tahun)
                             ->whereMonth('tanggal', $bulan)
                             ->get()
                             ->keyBy(fn($h) => $h->tanggal->toDateString());

        /* 3️⃣  Ambil pegawai + presensi + izin bulan ini ---------------------- */
        $pegawaiQuery = Karyawan::with([
            'absensi' => fn($q) => $q->whereYear('tanggal',$tahun)
                                     ->whereMonth('tanggal',$bulan),
            'izins'   => fn($q) => $q->where(function($sub)use($tahun,$bulan){
                $sub->whereYear('tanggal_awal', $tahun)->whereMonth('tanggal_awal', $bulan)
                     ->orWhereYear('tanggal_akhir',$tahun)->whereMonth('tanggal_akhir',$bulan);
            }),
        ]);

        if ($r->filled('search')) {
            $pegawaiQuery->where('nama','like','%'.$r->search.'%');
        }

        /** @var \Illuminate\Support\Collection $pegawaiList */
        $pegawaiList = $pegawaiQuery->get();   // TANPA paginasi

        /* 4️⃣  Helper parse jam (toleran terhadap “2025-04-14 07:08:00” atau “07:08”) */
        $toCarbon = function(string $tgl, ?string $time): ?Carbon {
            if (!$time) return null;
            $time = trim($time);

            // sudah full datetime → langsung parse
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $time)) {
                return Carbon::parse($time);
            }

            // hanya HH:mm[:ss]
            if (preg_match('/^\d{2}:\d{2}/', $time)) {
                return Carbon::parse("$tgl ".substr($time,0,5));
            }
            return null;    // format tak dikenal
        };

        /* 5️⃣  Hitung total menit + jadwal harian untuk tiap pegawai ---------- */
        foreach ($pegawaiList as $peg) {

            /* 5-a. peta izin (YYYY-MM-DD ⇒ kode) */
            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                foreach (CarbonPeriod::create(
                        $iz->tanggal_awal,
                        $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
                    $mapIzin[$d->toDateString()] = strtok($iz->jenis_ijin,' ');
                }
            }

            /* 5-b. total menit satu bulan (skip libur + izin) */
            $totalMenit = 0;
            foreach ($peg->absensi as $row) {
                $tglStr = $row->tanggal->toDateString();
                if (isset($holidayMap[$tglStr]) || isset($mapIzin[$tglStr])) continue;

                $in  = $toCarbon($tglStr, $row->jam_masuk);
                $out = $toCarbon($tglStr, $row->jam_pulang);

                if ($in && $out && $out->gt($in)) {
                    $totalMenit += $in->diffInMinutes($out);
                } elseif (($in && !$out) || (!$in && $out)) {
                    $totalMenit += $this->defaultMinutes;
                }
            }

            /* 5-c. tabel harian utk segment */
            $daily   = [];
            $mapPres = $peg->absensi->keyBy(fn($p)=>$p->tanggal->toDateString());

            foreach ($tanggalList as $d) {
                $tglStr  = sprintf('%04d-%02d-%02d',$tahun,$bulan,$d);
                $weekday = Carbon::parse($tglStr)->dayOfWeekIso;   // 6=Sabtu 7=Minggu

                /* sabtu / minggu */
                if ($weekday === 6) { $daily[$d]=['type'=>'libur','label'=>'Sabtu'];  continue; }
                if ($weekday === 7) { $daily[$d]=['type'=>'libur','label'=>'Minggu']; continue; }

                /* libur nasional / manual */
                if ($h = $holidayMap[$tglStr] ?? null) {
                    $daily[$d]=['type'=>'libur','label'=>$h->keterangan]; continue;
                }

                /* izin */
                if (isset($mapIzin[$tglStr])) {
                    $daily[$d]=['type'=>'izin','label'=>$mapIzin[$tglStr]]; continue;
                }

                /* presensi */
                if ($row = $mapPres[$tglStr] ?? null) {
                    $in   = $row->jam_masuk  ? substr($row->jam_masuk , -8, 5) : null;
                    $out  = $row->jam_pulang ? substr($row->jam_pulang, -8, 5) : null;

                    if ($in && $out) {
                        // presensi lengkap
                        $late = $in > '07:30';
                        $type = $late ? 'terlambat' : 'hadir';
                    } else {
                        // hanya jam masuk / hanya jam pulang  → dianggap kosong (merah)
                        $type = 'terlambat';
                    }

                    $daily[$d] = [
                        'type'  => $type,
                        'label' => ($in ?: '--:--').' – '.($out ?: '--:--'),
                    ];
                } else {
                    $daily[$d] = ['type'=>'kosong','label'=>'-'];
                }

            }

            /* simpan ke model */
            $peg->absensi_harian = $daily;
            $peg->total_menit    = $totalMenit;  
            $peg->total_fmt = $this->fmtHariJamMenit($totalMenit);
     // int!
        }   
    
    

        /* 6️⃣  SORT sesudah semua menit terisi ------------------------------- */
        $pegawaiList = match ($sort) {
            'total_desc' => $pegawaiList->sortByDesc('total_menit')->values(),
            'total_asc'  => $pegawaiList->sortBy('total_menit')->values(),
            'nama_desc'  => $pegawaiList->sortByDesc('nama', SORT_NATURAL|SORT_FLAG_CASE)->values(),
            'nama_asc',  ''  => $pegawaiList->sortBy('nama', SORT_NATURAL|SORT_FLAG_CASE)->values(),
            default      => $pegawaiList->values(),
        };

        /* 7️⃣  kirim ke view -------------------------------------------------- */
        return view('absensi.rekap', compact(
            'pegawaiList','tanggalList','bulan','tahun',
            'segment','daysInMonth','holidayMap','sort'
        ));
    }

    private function fmtHariJamMenit(int $menit): string
    {
        // —— kalau 1 hari = 24 jam ——  
        // $hari = intdiv($menit, 60*24);
        // $sisa = $menit % (60*24);

        // —— kalau 1 hari kerja = 7 jam 30 menit ——  
        $menitPerHariKerja = $this->defaultMinutes;   // 450
        $hari = intdiv($menit, $menitPerHariKerja);
        $sisa = $menit % $menitPerHariKerja;

        $jam  = intdiv($sisa, 60);
        $mnt  = $sisa % 60;

        return sprintf('%d hari %d jam %d menit', $hari, $jam, $mnt);
    }
    
    /* ==========================================================
     *  R E K A P   T A H U N A N – tanpa perubahan berarti
     * ========================================================== */
    public function rekapTahunan(Request $r)
    {
        $tahun = (int) $r->input('tahun', date('Y'));

        $pegawaiList = Karyawan::with([
            'absensi' => fn ($q) => $q->whereYear('tanggal', $tahun)
        ])->get();

        $toCarbon = fn (string $tgl, ?string $w) =>
            $w ? (str_contains($w, ' ') ? Carbon::parse($w)
                                        : Carbon::parse("$tgl $w"))
               : null;

        foreach ($pegawaiList as $peg) {
            $menitPerBulan = array_fill(1, 12, 0);

            foreach ($peg->absensi as $row) {
                $tglStr = $row->tanggal instanceof Carbon
                          ? $row->tanggal->toDateString()
                          : $row->tanggal;
                $idx = Carbon::parse($tglStr)->month;

                $in  = $toCarbon($tglStr, $row->jam_masuk);
                $out = $toCarbon($tglStr, $row->jam_pulang);

                if ($in && $out && $out->gt($in)) {
                    $menitPerBulan[$idx] += $in->diffInMinutes($out);
                } elseif (($in && !$out) || (!$in && $out)) {
                    $menitPerBulan[$idx] += $this->defaultMinutes;
                }
            }

            $fmt = fn (int $m) => sprintf('%02d:%02d', intdiv($m, 60), $m % 60);

            $peg->rekap_tahunan = array_map($fmt, $menitPerBulan);
            $peg->total_menit   = array_sum($menitPerBulan);   // konsisten dgn rekap bulanan
            $peg->total_tahun   = $fmt(array_sum($menitPerBulan));
        }

        return view('absensi.rekap-tahunan', compact('pegawaiList', 'tahun'));
    }

    /* ==========================================================
     *  CRUD  Tanggal Merah
     * ========================================================== */
    public function storeHoliday(Request $r)
    {
        $data = Validator::make($r->all(), [
            'tanggal'    => ['required','date'],
            'keterangan' => ['required','string','max:100'],
        ])->validate();

        Holiday::updateOrCreate(
            ['tanggal' => $data['tanggal']],
            ['keterangan' => $data['keterangan']]
        );

        return back()->with('holiday_success',
            'Tanggal '.$data['tanggal'].' ditandai: '.$data['keterangan']);
    }

    public function destroyHoliday(string $id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return back()->with('holiday_success',
            'Tanggal '.$holiday->tanggal->format('d-m-Y').' dihapus.');
    }
}
