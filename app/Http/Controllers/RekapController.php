<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\IzinPresensi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RekapController extends Controller
{
    /**
     * Halaman rekap presensi + izin
     * URL contoh: /rekap?bulan=7&tahun=2025&segment=2
     */
    public function rekap(Request $request)
    {
        /* ─── 1. Parameter & validasi ─────────────────────────────── */
        $bulan   = (int) $request->input('bulan',  date('m'));
        $tahun   = (int) $request->input('tahun',  date('Y'));
        $segment = (int) $request->input('segment', 1);

        $bulan   = max(1, min(12, $bulan));
        $segment = max(1, min(3,  $segment));

        /* ─── 2. Tentukan deret tanggal untuk segment ─────────────── */
        $jumlahHari = Carbon::create($tahun, $bulan)->daysInMonth;

        [$start, $end] = match ($segment) {
            1       => [1, 10],
            2       => [11, 20],
            default => [21, $jumlahHari],
        };

        $tanggalList = range($start, $end);
        $periodeBulan = CarbonPeriod::create(
            "$tahun-$bulan-01",
            "$tahun-$bulan-$jumlahHari"
        );

        /* ─── 3. Ambil data presensi & izin secara eager load ─────── */
        $pegawaiQuery = Karyawan::with([
            // presensi 1 bulan penuh
            'absensi' => fn($q) => $q->whereYear('tanggal', $tahun)
                                     ->whereMonth('tanggal', $bulan),

            // izin yg MENYENTUH bulan tsb
            'izins'   => fn($q) => $q->where(function ($sub) use ($tahun, $bulan) {
                $sub->whereYear('tanggal_awal',  $tahun)
                    ->whereMonth('tanggal_awal', $bulan)
                    ->orWhereYear('tanggal_akhir', $tahun)
                    ->whereMonth('tanggal_akhir', $bulan);
            })
        ]);

        // filter nama jika ada
        if ($request->filled('search')) {
            $pegawaiQuery->where('nama', 'like', '%' . $request->search . '%');
        }

        $pegawaiList = $pegawaiQuery->paginate(10)->withQueryString();

        /* ─── 4. Olah data per-pegawai ────────────────────────────── */
        foreach ($pegawaiList as $pegawai) {

            /* 4a. Petakan presensi: [tgl => {jam_masuk, jam_pulang}] */
            $presensiTgl = $pegawai->absensi->keyBy(fn($p) => $p->tanggal->toDateString());

            /* 4b. Petakan izin: [tgl => label izin]  (rentang tanggal) */
            $izinTgl = collect();
            foreach ($pegawai->izins as $izin) {

                $range = CarbonPeriod::create(
                    $izin->tanggal_awal,
                    $izin->tanggal_akhir ?? $izin->tanggal_awal
                );

                foreach ($range as $tgl) {
                    // singkatan izin → "CB", "SAKIT", dsb
                    $izinTgl[$tgl->toDateString()] = strtok($izin->jenis_ijin, ' ');
                }
            }

            /* 4c. Susun kolom harian hanya utk segment aktif */
            $absensiPerTanggal = [];
            $totalMenitHadir   = 0;

            foreach ($tanggalList as $tgl) {
                $date = Carbon::create($tahun, $bulan, $tgl)->toDateString();

                /* — prioritas izin */
                if (isset($izinTgl[$date])) {
                    $absensiPerTanggal[$tgl] = [
                        'type'  => 'izin',
                        'label' => $izinTgl[$date],
                    ];
                    continue;
                }

                /* — presensi */
                if ($p = $presensiTgl[$date] ?? null) {
                    $jamMasuk  = Carbon::parse($p->jam_masuk);
                    $jamPulang = Carbon::parse($p->jam_pulang);

                    if ($jamPulang->greaterThan($jamMasuk)) {
                        $menit = $jamMasuk->diffInMinutes($jamPulang);
                        $totalMenitHadir += $menit;
                    }

                    $absensiPerTanggal[$tgl] = [
                        'type'   => 'hadir',
                        'label'  => $jamMasuk->format('H:i') . ' - ' . $jamPulang->format('H:i'),
                    ];
                } else {
                    $absensiPerTanggal[$tgl] = [
                        'type'  => 'kosong',
                        'label' => '/',
                    ];
                }
            }

            /* 4d. Tambah properti ke model (untuk dipakai di Blade) */
            $pegawai->absensi_harian = $absensiPerTanggal;
            $pegawai->total_menit    = $totalMenitHadir;
        }

        /* ─── 5. Kirim ke view ────────────────────────────────────── */
        return view('absensi.rekap', [
            'pegawaiList'  => $pegawaiList,
            'tanggalList'  => $tanggalList,
            'bulan'        => $bulan,
            'tahun'        => $tahun,
            'segment'      => $segment,
            'jumlahHari'   => $jumlahHari,
        ]);
    }

    public function rekapTahunan(Request $request)
{
    $tahun = (int) $request->input('tahun', date('Y'));

    // Ambil semua karyawan
    $pegawaiList = Karyawan::with(['absensi' => function ($q) use ($tahun) {
        $q->whereYear('tanggal', $tahun);
    }])->get();

    // Hitung total menit kerja per bulan
    foreach ($pegawaiList as $pegawai) {
        $rekap = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $total = $pegawai->absensi
                ->filter(fn($absen) => $absen->tanggal->month == $bulan)
                ->reduce(function ($carry, $item) {
                    $masuk = \Carbon\Carbon::parse($item->jam_masuk);
                    $pulang = \Carbon\Carbon::parse($item->jam_pulang);
                    return $carry + ($pulang > $masuk ? $masuk->diffInMinutes($pulang) : 0);
                }, 0);

            $rekap[$bulan] = $total;
        }

        $pegawai->rekap_tahunan = $rekap;
    }

    return view('absensi.rekap-tahunan', compact('pegawaiList', 'tahun'));
}

}
