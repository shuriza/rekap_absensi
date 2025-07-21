<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\RekapAbsensiBulananExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RekapAbsensiTahunanExport;
use App\Exports\IzinBulananExport;
use Carbon\Carbon;



class ExportRekapController extends Controller
{
    public function exportBulanan(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));

        return Excel::download(
            new RekapAbsensiBulananExport($bulan, $tahun),
            "Rekap_Absensi_Bulanan_{$tahun}_{$bulan}.xlsx"
        );
    }
    public function exportTahunan(Request $request)
    {
        $tahun = $request->input('tahun', now()->year);

        return Excel::download(
            new RekapAbsensiTahunanExport($tahun),
            "Rekap_Absensi_Tahunan_{$tahun}.xlsx"
        );
    }
    public function exportIzinBulanan(Request $request)
    {
        // pakai tanggal awal sebagai penentu bulan‑tahun
        $start = $request->input('start_date');
        if (!$start) {
            return back()->withErrors('Silakan pilih kolom "Dari" terlebih dahulu.');
        }

        [$tahun, $bulan] = [Carbon::parse($start)->year, Carbon::parse($start)->month];

        return Excel::download(
            new IzinBulananExport($bulan, $tahun),
            "Izin_Bulanan_{$tahun}_{$bulan}.xlsx"
        );
    }


}
