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
    /** Jika jam pulang / masuk hilang → 7 jam 30 menit = 450 menit */
    private int $defaultMinutes = 7 * 60 + 30;

    /* ============================================================
     *  R E K A P   B U L A N A N
     * ============================================================ */
    public function rekap(Request $r)
    {
        /* 1. ── Parameter & validasi ───────────────────────────── */
        $bulan   = max(1,  min(12, (int) $r->input('bulan',  date('m'))));
        $tahun   =         (int) $r->input('tahun',  date('Y'));
        $segment = max(1,  min(3,  (int) $r->input('segment', 1)));

        /* 2. ── Hitung rentang segment (1-10 / 11-20 / 21-akhir) ─ */
        $daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;
        [$start, $end] = match ($segment) {
            1       => [1, 10],
            2       => [11, 20],
            default => [21, $daysInMonth],
        };
        $tanggalList = range($start, $end);

        /* 3. ── Ambil daftar hari libur bulan ini ──────────────── */
        $holidayMap = Holiday::whereYear('tanggal',  $tahun)
                             ->whereMonth('tanggal', $bulan)
                             ->get()
                             ->keyBy(fn ($h) => $h->tanggal->toDateString()); // key = YYYY-MM-DD

        /* 4. ── Query karyawan + presensi + izin se-bulan ─────── */
        $pegawaiQuery = Karyawan::with([
            'absensi' => fn ($q) => $q->whereYear('tanggal', $tahun)
                                      ->whereMonth('tanggal', $bulan),
            'izins'   => fn ($q) => $q->where(function ($sub) use ($tahun, $bulan) {
                $sub->whereYear('tanggal_awal',  $tahun)->whereMonth('tanggal_awal',  $bulan)
                    ->orWhereYear('tanggal_akhir', $tahun)->whereMonth('tanggal_akhir', $bulan);
            }),
        ]);

        if ($r->filled('search')) {
            $pegawaiQuery->where('nama', 'like', '%' . $r->search . '%');
        }

        $pegawaiList = $pegawaiQuery->paginate(10)->withQueryString();

        /* Helper parse time → Carbon|null */
        $toCarbon = fn (string $tgl, ?string $w) =>
            $w ? (str_contains($w, ' ') ? Carbon::parse($w)
                                        : Carbon::parse("$tgl $w"))
               : null;

        /* 5. ── Proses tiap pegawai ───────────────────────────── */
        foreach ($pegawaiList as $peg) {

            /* 5-a. Peta izin (tanggal ⇒ singkatan) */
            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                $period = CarbonPeriod::create($iz->tanggal_awal,
                           $iz->tanggal_akhir ?? $iz->tanggal_awal);
                foreach ($period as $d) {
                    $mapIzin[$d->toDateString()] = strtok($iz->jenis_ijin, ' ');
                }
            }

            /* 5-b. Hitung total menit kerja 1 bulan (skip libur & izin) */
            $totalMenit = 0;
            foreach ($peg->absensi as $row) {
                $tglStr = $row->tanggal->toDateString();

                if (isset($holidayMap[$tglStr]) || isset($mapIzin[$tglStr])) {
                    continue;                                   // abaikan hari libur / izin
                }

                $in  = $toCarbon($tglStr, $row->jam_masuk);
                $out = $toCarbon($tglStr, $row->jam_pulang);

                if ($in && $out && $out->greaterThan($in)) {
                    $totalMenit += $in->diffInMinutes($out);
                } elseif (($in && !$out) || (!$in && $out)) {
                    $totalMenit += $this->defaultMinutes;
                }
            }

            /* 5-c. Susun kolom harian untuk segment aktif */
            $daily = [];
            $mapPres = $peg->absensi->keyBy(fn ($p) => $p->tanggal->toDateString());

            foreach ($tanggalList as $d) {
                $tglStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);

                /* ── Prioritas #1: Libur nasional / manual */
                if ($h = $holidayMap[$tglStr] ?? null) {
                    $daily[$d] = ['type' => 'libur', 'label' => $h->keterangan];
                    continue;
                }

                /* ── Prioritas #2: Izin */
                if (isset($mapIzin[$tglStr])) {
                    $daily[$d] = ['type' => 'izin', 'label' => $mapIzin[$tglStr]];
                    continue;
                }

                /* ── Prioritas #3: Presensi */
                if ($row = $mapPres[$tglStr] ?? null) {
                    $inTxt  = $toCarbon($tglStr, $row->jam_masuk)?->format('H:i') ?? '-';
                    $outTxt = $toCarbon($tglStr, $row->jam_pulang)?->format('H:i') ?? '-';
                    $daily[$d] = ['type' => 'hadir', 'label' => "$inTxt - $outTxt"];
                } else {
                    $daily[$d] = ['type' => 'kosong', 'label' => '/'];
                }
            }

            /* Simpan ke model untuk dipakai view */
            $peg->absensi_harian = $daily;
            $peg->total_menit    = $totalMenit;
        }

        return view('absensi.rekap', compact(
            'pegawaiList', 'tanggalList', 'bulan', 'tahun', 'segment', 'daysInMonth', 'holidayMap'
        ));
    }

    /* ============================================================
     *  R E K A P   T A H U N A N
     * ============================================================ */
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

            $fmt = fn ($m) => str_pad($m / 60, 2, '0', STR_PAD_LEFT)
                           . ':' . str_pad($m % 60, 2, '0', STR_PAD_LEFT);

            $peg->rekap_tahunan = array_map($fmt, $menitPerBulan);
            $peg->total_tahun   = $fmt(array_sum($menitPerBulan));
        }

        return view('absensi.rekap-tahunan', compact('pegawaiList', 'tahun'));
    }

    /* ============================================================
     *  S I M P A N   T A N G G A L   H O L I D A Y
     * ============================================================ */
    public function storeHoliday(Request $r)
    {
        $data = Validator::make($r->all(), [
            'tanggal'    => ['required', 'date'],
            'keterangan' => ['required', 'string', 'max:100'],
        ])->validate();

        Holiday::updateOrCreate(
            ['tanggal' => $data['tanggal']],
            ['keterangan' => $data['keterangan']]
        );

        return back()->with('holiday_success',
            'Tanggal ' . $data['tanggal'] . ' ditandai: ' . $data['keterangan']);
    }

    public function destroyHoliday(string $id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return back()->with('holiday_success',
            'Tanggal '.$holiday->tanggal->format('d-m-Y').' dihapus.');
    }
}
