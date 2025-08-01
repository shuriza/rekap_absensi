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

                /* 5-a. BUAT map izin: simpan OBJEK, bukan string */
                $mapIzin = [];
                foreach ($peg->izins as $iz) {
                    foreach (CarbonPeriod::create(
                            $iz->tanggal_awal,
                            $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
                        $mapIzin[$d->toDateString()] = $iz;     // ⟵ simpan objek
                    }
                }



/* 5️⃣  TOTAL MENIT SE-BULAN PENUH  (tidak tergantung segment) -------------- *
 * • Hari kerja (Sen–Jum) tanpa presensi = 7 jam 30 m (defaultMinutes).      *
 * • Hari libur/izin dilewati.                                              *
 * • Presensi sebagian (hanya jam masuk / pulang) = 7 jam 30 m.             *
 * • Presensi lengkap = selisih jam masuk-pulang.                           */
$totalMenit = 0;

/* Peta presensi → agar lookup per-tanggal cepat */
$mapPres = $peg->absensi->keyBy(fn($p) => $p->tanggal->toDateString());

for ($d = 1; $d <= $daysInMonth; $d++) {
    $tglStr  = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
    $weekday = Carbon::parse($tglStr)->dayOfWeekIso;   // 6 = Sabtu, 7 = Minggu

    /* ─── Abaikan akhir-pekan, tanggal merah & izin ─── */
    if ($weekday >= 6)                continue;   // Sabtu / Minggu
    if (isset($holidayMap[$tglStr]))  continue;   // Libur nasional/manual
    if (isset($mapIzin[$tglStr]))     continue;   // Ada izin pegawai

    /* ─── Hitung presensi ─── */
    $row = $mapPres[$tglStr] ?? null;
    $in  = $row ? $toCarbon($tglStr, $row->jam_masuk)  : null;
    $out = $row ? $toCarbon($tglStr, $row->jam_pulang) : null;

    if ($in && $out && $out->gt($in)) {
        // Presensi lengkap → pakai selisih sebenarnya
        $totalMenit += $in->diffInMinutes($out);
    } else {
        /* • Tidak ada presensi sama sekali
           • Atau hanya jam masuk / pulang yang tercatat
           ➜ Tetap dihitung 7 jam 30 menit (defaultMinutes) */
        $totalMenit += $this->defaultMinutes;   // 450 menit
    }
}

/* simpan ke model (nilai ini dipakai Blade & Export) */
$peg->total_menit = $totalMenit;
$peg->total_fmt   = $this->fmtHariJamMenit($totalMenit);

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

                /* 5-c. Saat isi $daily untuk type ‘izin’ */
                if (isset($mapIzin[$tglStr])) {
                    $iz = $mapIzin[$tglStr];                   // instansi IzinPresensi
                    $daily[$d] = [
                        'type'  => 'izin',
                        'label' => strtok($iz->jenis_ijin,' '),
                        /* extra untuk modal */
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


                /* presensi */
                /* (#3) presensi → hadir / terlambat / kosong / di-luar-waktu */
                if ($row = $mapPres[$tglStr] ?? null) {

                    // ambil keterangan jika sudah dihitung sebelumnya di DB
                    $keterangan = strtolower(trim($row->keterangan ?? ''));

                    // mapping keterangan → type untuk pewarnaan
                    $type = match ($keterangan) {
                        'diluar waktu absen' => 'kosong',      // merah
                        'terlambat'          => 'terlambat',   // kuning
                        'tepat waktu'        => 'hadir',       // tidak berwarna
                        default              => null,          // belum ada keterangan
                    };

                    // ─── kalau belum ada keterangan (type null) fallback ke hitung manual ───
                    if ($type === null) {
                        // ***logika lama Anda di sini***  (atau dibiarkan kosong)
                        $in  = $row->jam_masuk  ? substr($row->jam_masuk , -8, 5) : null;
                        $out = $row->jam_pulang ? substr($row->jam_pulang, -8, 5) : null;
                        $late = $in && $in > '07:30';
                        $type = $in || $out ? ($late ? 'terlambat' : 'hadir') : 'kosong';
                    }

                    // label tetap jam-jam supaya masih terlihat
                    $in  = $row->jam_masuk  ? substr($row->jam_masuk , -8, 5) : '--:--';
                    $out = $row->jam_pulang ? substr($row->jam_pulang, -8, 5) : '--:--';

                    $daily[$d] = [
                        'type'  => $type,
                        'label' => "$in – $out",
                    ];
                }
                // kalau tidak masuk kondisi apa-pun, isi default
                    $daily[$d] ??= ['type' => 'kosong', 'label' => '-'];


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

        $listJenis = [
            'DL (DINAS LUAR) [TIDAK ADA PENGURANGAN]',
            'CB (CUTI BERSALIN) [TIDAK ADA PENGURANGAN]',
            'CM (CUTI MELAHIRKAN) [TIDAK ADA PENGURANGAN]',
            'CT (CUTI TAHUNAN)',
            'Sakit (Surat Dokter)',
            'Sakit (Tanpa Surat Dokter)',
            'Keperluan Keluarga',
            'Keperluan Pribadi',
        ];
        $tipeIjin  = ['Ijin Penuh','Ijin Setengah','Terlambat','Pulang Cepat'];
        // ⇩ kirim ke view
        return view('absensi.rekap', compact(
            'pegawaiList','tanggalList','bulan','tahun',
            'segment','daysInMonth','holidayMap','sort',
            // ⇩ kirim ke view
            'listJenis','tipeIjin'
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
    *  R E K A P   T A H U N A N  (dengan SEARCH & SORT)
    * ========================================================== */
public function rekapTahunan(Request $r)
    {
        $tahun  = (int) $r->input('tahun', date('Y'));
        $sort   = $r->input('sort');   // '' | nama_asc | nama_desc | total_asc | total_desc
        $search = $r->input('search'); // filter nama

        /* ---------- query karyawan + absensi setahun ----------- */
        $pegawaiQuery = Karyawan::with([
            'absensi' => fn($q) => $q->whereYear('tanggal', $tahun)
        ]);

        if ($search) {
            $pegawaiQuery->where('nama', 'like', "%{$search}%");
        }

        $pegawaiList = $pegawaiQuery->get(); // tanpa paginasi

        /* ---------- helper parse jam --------------------------- */
        $toCarbon = function (string $tgl, ?string $time): ?Carbon {
            if (!$time) return null;
            return str_contains($time, ' ')
                ? Carbon::parse($time)                      // full datetime
                : Carbon::parse("$tgl " . substr($time, 0, 5)); // HH:mm
        };

        /* ---------- hitung menit & format ---------------------- */
        foreach ($pegawaiList as $peg) {
            // array menit per bulan 1..12
            $menitPerBulan = array_fill(1, 12, 0);

            foreach ($peg->absensi as $row) {
                $tglStr = $row->tanggal instanceof Carbon
                    ? $row->tanggal->toDateString()
                    : $row->tanggal;
                $idx = Carbon::parse($tglStr)->month; // 1-12

                $in  = $toCarbon($tglStr, $row->jam_masuk);
                $out = $toCarbon($tglStr, $row->jam_pulang);

                if ($in && $out && $out->gt($in)) {
                    $menitPerBulan[$idx] += $in->diffInMinutes($out);
                } elseif (($in && !$out) || (!$in && $out)) {
                    $menitPerBulan[$idx] += $this->defaultMinutes; // 450
                }
            }

            // simpan juga menit raw agar bisa dipakai di view
            $peg->menitPerBulan = $menitPerBulan;

            // format ke HH:MM
            $HHMM = fn(int $m) => sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            $peg->rekap_tahunan = array_map($HHMM, $menitPerBulan);

            // total tahunan dalam menit & format
            $totMenit = array_sum($menitPerBulan);
            $peg->total_menit = $totMenit;

            $hari  = intdiv($totMenit, 1440);
            $sisa  = $totMenit % 1440;
            $jam   = str_pad(intdiv($sisa, 60), 2, '0', STR_PAD_LEFT);
            $menit = str_pad($sisa % 60, 2, '0', STR_PAD_LEFT);
            $peg->total_fmt = "{$hari}h {$jam}j {$menit}m";
        }

        /* ---------- SORT setelah total_menit tersedia ---------- */
        $pegawaiList = match ($sort) {
            'total_desc' => $pegawaiList->sortByDesc('total_menit')->values(),
            'total_asc'  => $pegawaiList->sortBy('total_menit')->values(),
            'nama_desc'  => $pegawaiList->sortByDesc('nama', SORT_NATURAL|SORT_FLAG_CASE)->values(),
            'nama_asc', ''=> $pegawaiList->sortBy('nama', SORT_NATURAL|SORT_FLAG_CASE)->values(),
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
        $holiday->delete();

        return back()->with('holiday_success',
            'Tanggal '.$holiday->tanggal->format('d-m-Y').' dihapus.');
    }
}
