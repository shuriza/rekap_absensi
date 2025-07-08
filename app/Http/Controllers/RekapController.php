<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use Carbon\Carbon;

class RekapController extends Controller
{
    public function Rekap(Request $request)
    {
        $bulan = (int) $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));
        $segment = (int) $request->input('segment', 1);

        // Validasi nilai
        $bulan = max(1, min(12, $bulan));
        $segment = max(1, min(3, $segment));

        $jumlahHari = Carbon::create($tahun, $bulan)->daysInMonth;

        // Tentukan rentang segment tanggal
        switch ($segment) {
            case 1: $start = 1; $end = 10; break;
            case 2: $start = 11; $end = 20; break;
            case 3: default: $start = 21; $end = $jumlahHari; break;
        }

        $tanggalList = range($start, $end);

        // Ambil data absensi 1 bulan penuh (bukan hanya segment), karena kita akan hitung akumulasi total
        $pegawaiQuery = Karyawan::with(['absensi' => function ($query) use ($tahun, $bulan) {
            $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }]);

        // Filter nama jika ada
        if ($request->filled('search')) {
            $pegawaiQuery->where('nama', 'like', '%' . $request->search . '%');
        }

        $pegawaiList = $pegawaiQuery->paginate(10);

        foreach ($pegawaiList as $pegawai) {
            $absensiPerTanggal = [];
            $totalMenitHadir = 0;

            foreach ($tanggalList as $tgl) {
                $absensiPerTanggal[$tgl] = '-';
            }

            foreach ($pegawai->absensi as $absen) {
                $day = (int) Carbon::parse($absen->tanggal)->format('d');

                $jamMasuk = $absen->jam_masuk ? Carbon::parse($absen->jam_masuk) : null;
                $jamPulang = $absen->jam_pulang ? Carbon::parse($absen->jam_pulang) : null;

                // Hitung selisih menit hadir jika lengkap
                if ($jamMasuk && $jamPulang && $jamPulang->greaterThan($jamMasuk)) {
                    $totalMenitHadir += $jamMasuk->diffInMinutes($jamPulang);
                }

                // Hanya tampilkan tanggal dalam segment yang aktif
                if (in_array($day, $tanggalList)) {
                    $masukStr = $jamMasuk ? $jamMasuk->format('H:i') : null;
                    $pulangStr = $jamPulang ? $jamPulang->format('H:i') : null;

                    $absensiPerTanggal[$day] = ($masukStr && $pulangStr) ? "$masukStr - $pulangStr" : ($masukStr ?: '-');
                }
            }

            $pegawai->absensi_harian = $absensiPerTanggal;
            $pegawai->total_menit = $totalMenitHadir; // akan dikonversi di blade
        }

        return view('absensi.rekap', compact(
            'pegawaiList',
            'tanggalList',
            'bulan',
            'tahun',
            'segment',
            'jumlahHari'
        ));
    }
}
