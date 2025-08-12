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
    /** 7 jam 30 menit (450 menit) bila hari kerja tanpa record / data tidak lengkap */
    private int $defaultMinutes = 7 * 60 + 30;

    /* ==========================================================
     *  R E K A P   B U L A N A N
     * ========================================================== */
    public function rekap(Request $r)
    {
        /* 1) Parameter dasar */
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

        /* 2) Libur nasional / manual */
        $holidayMap = Holiday::whereYear('tanggal',  $tahun)
                             ->whereMonth('tanggal', $bulan)
                             ->get()
                             ->keyBy(fn($h) => $h->tanggal->toDateString());

        /* 3) Ambil pegawai + absensi + izin bulan ini */
        $pegawaiQuery = Karyawan::with([
            'absensi' => fn($q) => $q->whereYear('tanggal',$tahun)
                                     ->whereMonth('tanggal',$bulan),
            'izins'   => fn($q) => $q->where(function($sub)use($tahun,$bulan){
                $sub->whereYear('tanggal_awal', $tahun)->whereMonth('tanggal_awal', $bulan)
                     ->orWhereYear('tanggal_akhir',$tahun)->whereMonth('tanggal_akhir',$bulan);
            }),
        ])->select('id', 'nama', 'departemen', 'is_ob');

        if ($r->filled('search')) {
            $pegawaiQuery->where('nama','like','%'.$r->search.'%');
        }

        /** @var \Illuminate\Support\Collection $pegawaiList */
        $pegawaiList = $pegawaiQuery->with('nonaktif_terbaru')->get()
            ->filter(fn ($k) => !$k->nonaktifPadaBulan($tahun, $bulan));

        /* Helper parse jam (toleran 2025-04-14 07:08:00 atau 07:08) */
        $toCarbon = function(string $tgl, ?string $time): ?Carbon {
            if (!$time) return null;
            $time = trim($time);
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $time)) {
                return Carbon::parse($time);
            }
            if (preg_match('/^\d{2}:\d{2}/', $time)) {
                return Carbon::parse("$tgl ".substr($time,0,5));
            }
            return null;
        };

        /* 5) Hitung total kedisiplinan + tabel harian */
        foreach ($pegawaiList as $peg) {

            /* Map izin: simpan objek utk kebutuhan modal */
            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                foreach (CarbonPeriod::create(
                        $iz->tanggal_awal,
                        $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
                    $mapIzin[$d->toDateString()] = $iz;
                }
            }

            /* 5a. TOTAL (kedisiplinan) SE-BULAN */
            $totalMenit = 0;
            $mapPres = $peg->absensi->keyBy(fn($p) => $p->tanggal->toDateString());

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $tglStr  = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                $weekday = Carbon::parse($tglStr)->dayOfWeekIso;   // 6 = Sabtu, 7 = Minggu

                // Lewati akhir pekan, tanggal merah, izin
                if ($weekday >= 6)               continue;
                if (isset($holidayMap[$tglStr])) continue;
                if (isset($mapIzin[$tglStr]))    continue;

                $row = $mapPres[$tglStr] ?? null;

                if ($row) {
                    if ($peg->is_ob) {
                        $in  = $toCarbon($tglStr, $row->jam_masuk);
                        $out = $toCarbon($tglStr, $row->jam_pulang);
                        $complete = $in && $out && $out->gt($in);
                        $pen = $complete ? 0 : $this->defaultMinutes;   // OB: lengkap=0, selain itu 450
                    } else {
                        $pen = is_numeric($row->penalty_minutes)
                            ? max(0, (int)$row->penalty_minutes)        // Non-OB: pakai hasil hitung
                            : $this->defaultMinutes;                    // fallback 450 kalau null
                    }
                } else {
                    $pen = $this->defaultMinutes;                       // hari kerja tanpa record
                }   

                $totalMenit += $pen;                                    // ⟵ tambahkan SEKALI

            }

            $peg->total_menit = $totalMenit;
            $peg->total_fmt   = $this->fmtHariJamMenit($totalMenit);
 // 1 hari = 7.5 jam

            /* 5b. TABEL HARIAN untuk segment (pewarnaan/label) */
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

                /* izin penuh */
                if (isset($mapIzin[$tglStr])) {
                    $iz = $mapIzin[$tglStr];
                    $daily[$d] = [
                        'type'  => 'izin',
                        'label' => strtok($iz->jenis_ijin,' '),
                        'id'    => $iz->id,
                        'tipe'  => $iz->tipe_ijin,
                        'jenis' => $iz->jenis_ijin,
                        'ket'   => $iz->keterangan,
                        'file'  => $iz->berkas,
                        'awal'  => $iz->tanggal_awal->toDateString(),
                        'akhir' => ($iz->tanggal_akhir ?? $iz->tanggal_awal)->toDateString(),
                    ];
                    continue;
                }

                /* presensi (untuk warna/label saja) */
                if ($row = $mapPres[$tglStr] ?? null) {
                    $keterangan = strtolower(trim($row->keterangan ?? ''));

                    // jam (bisa HH:mm atau null)
                    $inRaw  = $row->jam_masuk;
                    $outRaw = $row->jam_pulang;
                    $inC    = $toCarbon($tglStr, $inRaw);
                    $outC   = $toCarbon($tglStr, $outRaw);

                    // mapping dari keterangan (default null)
                    $type = match ($keterangan) {
                        'tidak valid'        => 'tidak_valid',
                        'diluar waktu absen' => 'kosong',
                        'terlambat'          => 'terlambat',
                        'pulang cepat'       => 'terlambat',  // warnai sama (kuning)
                        'tepat waktu'        => 'hadir',
                        default              => null,
                    };

                    // Override KHUSUS OB (hanya visual, total sudah di penalty_minutes)
                    if ($peg->is_ob && $weekday >= 1 && $weekday <= 5) {
                        if ($inC && $outC && $outC->gt($inC)) {
                            $type = 'hadir';
                        } elseif (($inC && !$outC) || (!$inC && $outC)) {
                            $type = 'tidak_valid';
                        } else {
                            $type = 'kosong';
                        }
                    }

                    if ($type !== null) {
                        $in  = $inRaw  ? substr($inRaw , -8, 5) : '--:--';
                        $out = $outRaw ? substr($outRaw, -8, 5) : '--:--';
                        $daily[$d] = ['type' => $type, 'label' => "$in – $out"];
                        continue;
                    }
                }

                /* default (tanpa data) */
                $daily[$d] ??= ['type' => 'kosong', 'label' => '-'];
            }

            /* simpan ke model utk view */
            $peg->absensi_harian = $daily;
            $peg->total_menit    = $totalMenit;
            $peg->total_fmt      = $this->fmtHariJamMenit($totalMenit);
        }

        /* 6) SORT */
        $pegawaiList = match ($sort) {
            'total_desc' => $pegawaiList->sortByDesc('total_menit')->values(),
            'total_asc'  => $pegawaiList->sortBy('total_menit')->values(),
            'nama_desc'  => $pegawaiList->sortByDesc('nama', SORT_NATURAL|SORT_FLAG_CASE)->values(),
            'nama_asc',  ''  => $pegawaiList->sortBy('nama', SORT_NATURAL|SORT_FLAG_CASE)->values(),
            default      => $pegawaiList->values(),
        };

        /* 7) kirim ke view */
        $rawJenis = [
            'DL - DINAS LUAR',
            'K - KEDINASAN',
            'S - SAKIT',
            'M - MELAHIRKAN',
            'AP - ALASAN PRIBADI',
            'L - LAINNYA',
        ];
        $listJenis = array_map(function($str) {
            $max = 80;
            return mb_strlen($str) > $max ? mb_substr($str, 0, $max-3).'...' : $str;
        }, $rawJenis);
        $jenisLengkap = $rawJenis;
        $tipeIjin  = ['PENUH','PARSIAL','TERLAMBAT','PULANG CEPAT','LAINNYA'];

        return view('absensi.rekap', compact(
            'pegawaiList','tanggalList','bulan','tahun',
            'segment','daysInMonth','holidayMap','sort',
            'listJenis','tipeIjin','jenisLengkap'
        ));
    }

    public function updateObBatch(Request $request)
    {
        $obIds = $request->input('ob_ids', []);
        Karyawan::whereIn('id', $obIds)->update(['is_ob' => true]);
        Karyawan::whereNotIn('id', $obIds)->update(['is_ob' => false]);
        return redirect()->back()->with('ob_success', 'Status OB diperbarui.');
    }

    private function fmtHariJamMenit(int $menit): string
    {
        // basis 1 hari kerja = 7 jam 30 menit
        $menitPerHariKerja = $this->defaultMinutes;   // 450
        $hari = intdiv($menit, $menitPerHariKerja);
        $sisa = $menit % $menitPerHariKerja;
        $jam  = intdiv($sisa, 60);
        $mnt  = $sisa % 60;
        return sprintf('%d hari %d jam %d menit', $hari, $jam, $mnt);
    }

    /* ==========================================================
    *  R E K A P   T A H U N A N  (SEARCH & SORT)
    * ========================================================== */
    public function rekapTahunan(Request $r)
    {
        $tahun  = (int) $r->input('tahun', date('Y'));
        $sort   = $r->input('sort');
        $search = $r->input('search');

        $pegawaiQuery = Karyawan::with([
            'absensi' => fn($q) => $q->whereYear('tanggal', $tahun),
            'izins'   => fn($q) => $q->where(function ($sub) use ($tahun) {
                $sub->whereYear('tanggal_awal', $tahun)
                    ->orWhereYear('tanggal_akhir', $tahun);
            }),
        ]);

        if ($search) {
            $pegawaiQuery->where('nama', 'like', "%{$search}%");
        }

        $pegawaiList = $pegawaiQuery->get();

        // libur setahun (sekali ambil)
        $holidayMap = Holiday::whereYear('tanggal', $tahun)->get()
            ->keyBy(fn($h) => $h->tanggal->toDateString());

        // helper parse jam
        $toCarbon = function (string $tgl, ?string $time): ?Carbon {
            if (!$time) return null;
            $time = trim($time);
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $time)) {
                return Carbon::parse($time);
            }
            if (preg_match('/^\d{2}:\d{2}/', $time)) {
                return Carbon::parse("$tgl " . substr($time, 0, 5));
            }
            return null;
        };

        foreach ($pegawaiList as $peg) {
            // peta izin setahun
            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
                    $mapIzin[$d->toDateString()] = true;
                }
            }

            // peta presensi setahun
            $mapPres = $peg->absensi->keyBy(fn($p) => $p->tanggal->toDateString());

            $menitPerBulan = array_fill(1, 12, 0);

            for ($bln = 1; $bln <= 12; $bln++) {
                $daysInMonth   = Carbon::create($tahun, $bln)->daysInMonth;
                $hadAny        = false;   // ada minimal 1 record di bulan ini?
                $noRecordCount = 0;       // jumlah hari kerja tanpa record

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $tglStr  = sprintf('%04d-%02d-%02d', $tahun, $bln, $d);
                    $weekday = Carbon::parse($tglStr)->dayOfWeekIso; // 6=Sabtu 7=Minggu

                    // lewati Sabtu/Minggu, tanggal merah, izin penuh
                    if ($weekday >= 6)               continue;
                    if (isset($holidayMap[$tglStr])) continue;
                    if (isset($mapIzin[$tglStr]))    continue;

                    $row = $mapPres[$tglStr] ?? null;

                    if ($row) {
                        $hadAny = true;

                        if ($peg->is_ob) {
                            // OB: in–out lengkap = 0; tidak lengkap = 450
                            $in  = $toCarbon($tglStr, $row->jam_masuk);
                            $out = $toCarbon($tglStr, $row->jam_pulang);
                            $complete = $in && $out && $out->gt($in);
                            $pen = $complete ? 0 : $this->defaultMinutes;
                        } else {
                            // non-OB: pakai penalty_minutes; fallback 450 bila null
                            $pen = $row->penalty_minutes;
                            $pen = is_numeric($pen) ? max(0, (int)$pen) : $this->defaultMinutes;
                        }

                        $menitPerBulan[$bln] += $pen;
                    } else {
                        // JANGAN langsung tambah 450 di sini
                        $noRecordCount++;
                    }
                }

                if ($hadAny) {
                    // bulan ini ada data → hari kerja tanpa record dihitung 7:30
                    $menitPerBulan[$bln] += $noRecordCount * $this->defaultMinutes;
                } else {
                    // benar-benar tanpa data sebulan penuh → 0
                    $menitPerBulan[$bln] = 0;
                }
            }


            // format & total
            $fmtHHMM = fn(int $m) => sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            $peg->menitPerBulan  = $menitPerBulan;
            $peg->rekap_tahunan  = array_map($fmtHHMM, $menitPerBulan);
            $peg->total_menit    = array_sum($menitPerBulan);
            $peg->total_fmt      = $this->fmtHariJamMenit($peg->total_menit);
        }

        // sort
        $pegawaiList = match ($sort) {
            'total_desc' => $pegawaiList->sortByDesc('total_menit')->values(),
            'total_asc'  => $pegawaiList->sortBy('total_menit')->values(),
            'nama_desc'  => $pegawaiList->sortByDesc('nama', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'nama_asc', '' => $pegawaiList->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            default      => $pegawaiList->values(),
        };

        return view('absensi.rekap-tahunan', compact('pegawaiList', 'tahun', 'search', 'sort'));
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
        $tanggalStr = $holiday->tanggal->format('d-m-Y');
        $holiday->delete();

        return back()->with('holiday_success',
            'Tanggal '.$tanggalStr.' dihapus.');
    }
}
