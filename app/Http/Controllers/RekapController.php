<?php
// app/Http/Controllers/AbsensiController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pegawai;
use App\Models\Absensi;
use App\Models\Karyawan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RekapController extends Controller
{
    public function Rekap(Request $request)
    {
        $bulan = (int) $request->input('bulan', date('m'));
        $bulan = max(1, min(12, $bulan));

        $tahun = $request->input('tahun', date('Y'));
        $segment = (int) $request->input('segment', 1);
        $segment = max(1, min(3, $segment));

        $jumlahHari = Carbon::create($tahun, $bulan)->daysInMonth;

        switch ($segment) {
            case 1:
                $start = 1;
                $end = 10;
                break;
            case 2:
                $start = 11;
                $end = 20;
                break;
            case 3:
            default:
                $start = 21;
                $end = $jumlahHari;
                break;
            }

        $tanggalList = range($start, $end);
        $tanggalFormat = array_map(function ($tgl) use ($bulan, $tahun) {
            return sprintf("%04d-%02d-%02d", $tahun, $bulan, $tgl);
        }, $tanggalList);

        $pegawaiQuery = Karyawan::with(['absensi' => function ($query) use ($tanggalFormat) {
            $query->whereIn('tanggal', $tanggalFormat);
        }]);

        if ($request->filled('search')) {
            $pegawaiQuery->where('nama', 'like', '%' . $request->search . '%');
        }

        $pegawaiList = $pegawaiQuery->paginate(10);

        return view('absensi.rekap', compact('pegawaiList', 'tanggalList', 'bulan', 'tahun', 'segment'));
    }

}
