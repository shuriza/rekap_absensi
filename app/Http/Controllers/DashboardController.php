<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\IzinPresensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->get('bulan', now()->month);
        $tahun = $request->get('tahun', now()->year);
        
        // Data untuk charts
        $kehadiranPerDepartemen = $this->getKehadiranPerDepartemen($bulan, $tahun);
        $trendAbsensi = $this->getTrendAbsensi($tahun);
        $topKaryawanPunctual = $this->getTopKaryawanPunctual($bulan, $tahun);
        $topKaryawanTerlambat = $this->getTopKaryawanTerlambat($bulan, $tahun);
        $statistikUmum = $this->getStatistikUmum($bulan, $tahun);
        
        return view('dashboard.analytics', compact(
            'kehadiranPerDepartemen',
            'trendAbsensi', 
            'topKaryawanPunctual',
            'topKaryawanTerlambat',
            'statistikUmum',
            'bulan',
            'tahun'
        ));
    }
    
    private function getKehadiranPerDepartemen($bulan, $tahun)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        $data = DB::table('karyawans as k')
            ->select([
                'k.departemen',
                DB::raw('COUNT(DISTINCT k.id) as total_karyawan'),
                DB::raw('COUNT(a.id) as total_hadir'),
                DB::raw('SUM(CASE 
                    WHEN k.is_ob = 1 THEN 0 
                    WHEN a.keterangan = "terlambat" THEN 1 
                    ELSE 0 
                END) as total_terlambat'),
                DB::raw('COUNT(i.id) as total_izin')
            ])
            ->leftJoin('absensis as a', function($join) use ($startDate, $endDate) {
                $join->on('k.id', '=', 'a.karyawan_id')
                     ->whereBetween('a.tanggal', [$startDate, $endDate]);
            })
            ->leftJoin('izin_presensi as i', function($join) use ($startDate, $endDate) {
                $join->on('k.id', '=', 'i.karyawan_id')
                     ->where(function($query) use ($startDate, $endDate) {
                         $query->whereBetween('i.tanggal_awal', [$startDate, $endDate])
                               ->orWhereBetween('i.tanggal_akhir', [$startDate, $endDate])
                               ->orWhere(function($q) use ($startDate, $endDate) {
                                   $q->where('i.tanggal_awal', '<=', $startDate)
                                     ->where('i.tanggal_akhir', '>=', $endDate);
                               });
                     });
            })
            ->where('k.status', 'aktif')
            ->groupBy('k.departemen')
            ->get();
            
        return $data;
    }
    
    private function getTrendAbsensi($tahun)
    {
        $data = [];
        
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
            $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
            
            $stats = DB::table('absensis as a')
                ->join('karyawans as k', 'a.karyawan_id', '=', 'k.id')
                ->select([
                    DB::raw('COUNT(*) as total_hadir'),
                    DB::raw('SUM(CASE 
                        WHEN k.is_ob = 1 THEN 0 
                        WHEN a.keterangan = "terlambat" THEN 1 
                        ELSE 0 
                    END) as total_terlambat'),
                    DB::raw('SUM(CASE 
                        WHEN k.is_ob = 1 AND a.jam_masuk IS NOT NULL AND a.jam_pulang IS NOT NULL THEN 1
                        WHEN k.is_ob = 0 AND a.keterangan = "tepat waktu" THEN 1 
                        ELSE 0 
                    END) as total_tepat_waktu')
                ])
                ->whereBetween('a.tanggal', [$startDate, $endDate])
                ->where('k.status', 'aktif')
                ->first();
                
            $izin = IzinPresensi::join('karyawans', 'izin_presensi.karyawan_id', '=', 'karyawans.id')
                ->where('karyawans.status', 'aktif')
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('tanggal_awal', '<=', $startDate)
                                ->where('tanggal_akhir', '>=', $endDate);
                          });
                })
                ->count();
                
            $data[] = [
                'bulan' => Carbon::create()->month($bulan)->translatedFormat('M'),
                'hadir' => $stats->total_hadir ?? 0,
                'terlambat' => $stats->total_terlambat ?? 0,
                'tepat_waktu' => $stats->total_tepat_waktu ?? 0,
                'izin' => $izin
            ];
        }
        
        return $data;
    }
    
    private function getTopKaryawanPunctual($bulan, $tahun, $limit = 10)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        return DB::table('karyawans as k')
            ->select([
                'k.nama',
                'k.departemen',
                'k.is_ob',
                DB::raw('COUNT(a.id) as total_hadir'),
                DB::raw('SUM(CASE 
                    WHEN k.is_ob = 1 AND a.jam_masuk IS NOT NULL AND a.jam_pulang IS NOT NULL THEN 1
                    WHEN k.is_ob = 0 AND a.keterangan = "tepat waktu" THEN 1 
                    ELSE 0 
                END) as tepat_waktu'),
                DB::raw('ROUND((SUM(CASE 
                    WHEN k.is_ob = 1 AND a.jam_masuk IS NOT NULL AND a.jam_pulang IS NOT NULL THEN 1
                    WHEN k.is_ob = 0 AND a.keterangan = "tepat waktu" THEN 1 
                    ELSE 0 
                END) / COUNT(a.id)) * 100, 2) as persentase_punctual')
            ])
            ->leftJoin('absensis as a', function($join) use ($startDate, $endDate) {
                $join->on('k.id', '=', 'a.karyawan_id')
                     ->whereBetween('a.tanggal', [$startDate, $endDate]);
            })
            ->where('k.status', 'aktif')
            ->havingRaw('COUNT(a.id) >= 5') // Minimal 5 hari hadir
            ->groupBy('k.id', 'k.nama', 'k.departemen', 'k.is_ob')
            ->orderByDesc('persentase_punctual')
            ->limit($limit)
            ->get();
    }
    
    private function getTopKaryawanTerlambat($bulan, $tahun, $limit = 10)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        $query = DB::table('karyawans as k')
            ->select([
                'k.nama',
                'k.departemen',
                'k.is_ob',
                DB::raw('COUNT(a.id) as total_hadir'),
                DB::raw('SUM(CASE 
                    WHEN k.is_ob = 0 AND a.keterangan = "terlambat" THEN 1 
                    ELSE 0 
                END) as total_terlambat'),
                DB::raw('ROUND((SUM(CASE 
                    WHEN k.is_ob = 0 AND a.keterangan = "terlambat" THEN 1 
                    ELSE 0 
                END) / COUNT(a.id)) * 100, 2) as persentase_terlambat')
            ])
            ->leftJoin('absensis as a', function($join) use ($startDate, $endDate) {
                $join->on('k.id', '=', 'a.karyawan_id')
                     ->whereBetween('a.tanggal', [$startDate, $endDate]);
            })
            ->where('k.status', 'aktif')
            ->where('k.is_ob', 0) // Hanya karyawan non-OB yang bisa terlambat
            ->havingRaw('COUNT(a.id) >= 5') // Minimal 5 hari hadir
            ->havingRaw('SUM(CASE WHEN a.keterangan = "terlambat" THEN 1 ELSE 0 END) > 0') // Ada record terlambat
            ->groupBy('k.id', 'k.nama', 'k.departemen', 'k.is_ob')
            ->orderByDesc('total_terlambat')
            ->limit($limit);
            
        $result = $query->get();
        
        // Jika tidak ada data terlambat, kembalikan collection kosong
        return $result->isEmpty() ? collect() : $result;
    }
    
    private function getStatistikUmum($bulan, $tahun)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        $totalKaryawan = Karyawan::where('status', 'aktif')->count();
        
        $absensiStats = DB::table('absensis as a')
            ->join('karyawans as k', 'a.karyawan_id', '=', 'k.id')
            ->select([
                DB::raw('COUNT(*) as total_kehadiran'),
                DB::raw('SUM(CASE 
                    WHEN k.is_ob = 0 AND a.keterangan = "terlambat" THEN 1 
                    ELSE 0 
                END) as total_terlambat'),
                DB::raw('SUM(CASE 
                    WHEN k.is_ob = 1 AND a.jam_masuk IS NOT NULL AND a.jam_pulang IS NOT NULL THEN 1
                    WHEN k.is_ob = 0 AND a.keterangan = "tepat waktu" THEN 1 
                    ELSE 0 
                END) as total_tepat_waktu')
            ])
            ->whereBetween('a.tanggal', [$startDate, $endDate])
            ->where('k.status', 'aktif')
            ->first();
            
        $totalIzin = IzinPresensi::join('karyawans', 'izin_presensi.karyawan_id', '=', 'karyawans.id')
            ->where('karyawans.status', 'aktif')
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                      ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                      ->orWhere(function($q) use ($startDate, $endDate) {
                          $q->where('tanggal_awal', '<=', $startDate)
                            ->where('tanggal_akhir', '>=', $endDate);
                      });
            })
            ->count();
        
        return [
            'total_karyawan' => $totalKaryawan,
            'total_kehadiran' => $absensiStats->total_kehadiran ?? 0,
            'total_terlambat' => $absensiStats->total_terlambat ?? 0,
            'total_tepat_waktu' => $absensiStats->total_tepat_waktu ?? 0,
            'total_izin' => $totalIzin,
            'persentase_kehadiran' => $totalKaryawan > 0 ? round((($absensiStats->total_kehadiran ?? 0) / ($totalKaryawan * Carbon::create($tahun, $bulan)->daysInMonth)) * 100, 2) : 0
        ];
    }
}
