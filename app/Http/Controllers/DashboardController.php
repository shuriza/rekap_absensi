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
        $topKaryawanTidakMasuk = $this->getTopKaryawanTidakMasuk($bulan, $tahun);
        $topKaryawanPenalty = $this->getTopKaryawanPenalty($bulan, $tahun);
        $statistikUmum = $this->getStatistikUmum($bulan, $tahun);
        
        return view('dashboard.analytics', compact(
            'kehadiranPerDepartemen',
            'trendAbsensi', 
            'topKaryawanPunctual',
            'topKaryawanTerlambat',
            'topKaryawanTidakMasuk',
            'topKaryawanPenalty',
            'statistikUmum',
            'bulan',
            'tahun'
        ));
    }
    
    private function getKehadiranPerDepartemen($bulan, $tahun)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        // Hitung jumlah hari kerja dalam bulan (tidak termasuk weekend)
        $hariKerja = 0;
        $tanggalKerja = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            if (!$current->isWeekend()) {
                $hariKerja++;
                $tanggalKerja[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        
        // Ambil data karyawan per departemen
        $departments = DB::table('karyawans')
            ->select('departemen', DB::raw('COUNT(*) as total_karyawan'))
            ->where('status', 'aktif')
            ->groupBy('departemen')
            ->get();
        
        $data = $departments->map(function($dept) use ($startDate, $endDate, $hariKerja, $tanggalKerja) {
            // Ambil semua karyawan di departemen ini
            $karyawanIds = DB::table('karyawans')
                ->where('departemen', $dept->departemen)
                ->where('status', 'aktif')
                ->pluck('id');
            
            $totalHadir = 0;
            $totalTerlambat = 0;
            $totalIzin = 0;
            $totalTidakMasuk = 0;
            $karyawanDenganAktivitas = 0; // Hanya hitung karyawan yang punya data
            
            foreach($karyawanIds as $karyawanId) {
                // Cek karyawan OB atau tidak
                $isOb = DB::table('karyawans')->where('id', $karyawanId)->value('is_ob');
                
                // Cek apakah karyawan ini punya data absensi atau izin di bulan ini
                $adaAbsensi = DB::table('absensis')
                    ->where('karyawan_id', $karyawanId)
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->exists();
                
                $adaIzin = DB::table('izin_presensi')
                    ->where('karyawan_id', $karyawanId)
                    ->where(function($query) use ($startDate, $endDate) {
                        $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                              ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                              ->orWhere(function($q) use ($startDate, $endDate) {
                                  $q->where('tanggal_awal', '<=', $startDate)
                                    ->where('tanggal_akhir', '>=', $endDate);
                              });
                    })
                    ->exists();
                
                // Skip karyawan yang tidak ada aktivitas sama sekali di bulan ini
                if (!$adaAbsensi && !$adaIzin) {
                    continue;
                }
                
                $karyawanDenganAktivitas++;
                
                // Ambil semua izin karyawan di bulan ini
                $izinDates = [];
                $izinRecords = DB::table('izin_presensi')
                    ->where('karyawan_id', $karyawanId)
                    ->where(function($query) use ($startDate, $endDate) {
                        $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                              ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                              ->orWhere(function($q) use ($startDate, $endDate) {
                                  $q->where('tanggal_awal', '<=', $startDate)
                                    ->where('tanggal_akhir', '>=', $endDate);
                              });
                    })
                    ->get();
                
                // Generate semua tanggal yang di-izin
                foreach($izinRecords as $izin) {
                    $izinStart = max($startDate, Carbon::parse($izin->tanggal_awal));
                    $izinEnd = min($endDate, Carbon::parse($izin->tanggal_akhir));
                    
                    $current = $izinStart->copy();
                    while($current <= $izinEnd) {
                        if(!$current->isWeekend()) {
                            $izinDates[] = $current->format('Y-m-d');
                        }
                        $current->addDay();
                    }
                }
                $izinDates = array_unique($izinDates);
                $totalIzin += count($izinDates);
                
                // Hitung kehadiran hanya untuk hari yang TIDAK di-izin
                $tanggalKerjaTanpaIzin = array_diff($tanggalKerja, $izinDates);
                
                if(!empty($tanggalKerjaTanpaIzin)) {
                    // Ambil data absensi untuk tanggal yang tidak di-izin
                    $absensiData = DB::table('absensis')
                        ->where('karyawan_id', $karyawanId)
                        ->whereIn('tanggal', $tanggalKerjaTanpaIzin)
                        ->get();
                    
                    $totalHadir += $absensiData->count();
                    
                    // Hitung terlambat (hanya untuk non-OB)
                    if(!$isOb) {
                        $totalTerlambat += $absensiData->where('keterangan', 'terlambat')->count();
                    }
                    
                    // Hitung tidak masuk (hari kerja tanpa izin yang tidak ada absensi)
                    $hariHadirDiTanggalTanpaIzin = $absensiData->pluck('tanggal')->map(function($date) {
                        return Carbon::parse($date)->format('Y-m-d');
                    })->toArray();
                    
                    $hariTidakMasuk = array_diff($tanggalKerjaTanpaIzin, $hariHadirDiTanggalTanpaIzin);
                    $totalTidakMasuk += count($hariTidakMasuk);
                }
            }
            
            return (object) [
                'departemen' => $dept->departemen,
                'total_karyawan' => $karyawanDenganAktivitas, // Hanya yang punya aktivitas
                'total_hadir' => $totalHadir,
                'total_terlambat' => $totalTerlambat,
                'total_izin' => $totalIzin,
                'total_tidak_masuk' => $totalTidakMasuk
            ];
        });
            
        return $data;
    }
    
    private function getTrendAbsensi($tahun)
    {
        $data = [];
        
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
            $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
            
            // Hitung jumlah hari kerja dalam bulan (tidak termasuk weekend)
            $hariKerja = 0;
            $current = $startDate->copy();
            while ($current <= $endDate) {
                if (!$current->isWeekend()) {
                    $hariKerja++;
                }
                $current->addDay();
            }
            
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
                
            // Hitung izin berdasarkan HARI, bukan record
            $izinHari = 0;
            $izinRecords = IzinPresensi::join('karyawans', 'izin_presensi.karyawan_id', '=', 'karyawans.id')
                ->where('karyawans.status', 'aktif')
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('tanggal_awal', '<=', $startDate)
                                ->where('tanggal_akhir', '>=', $endDate);
                          });
                })
                ->get();
            
            // Hitung total hari izin untuk semua karyawan
            foreach($izinRecords as $izin) {
                $izinStart = max($startDate, Carbon::parse($izin->tanggal_awal));
                $izinEnd = min($endDate, Carbon::parse($izin->tanggal_akhir));
                
                $current = $izinStart->copy();
                while($current <= $izinEnd) {
                    if(!$current->isWeekend()) {
                        $izinHari++;
                    }
                    $current->addDay();
                }
            }
            
            // Hitung total karyawan aktif yang punya data di bulan ini
            $karyawanDenganAktivitas = DB::table('karyawans as k')
                ->where('k.status', 'aktif')
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereExists(function($subquery) use ($startDate, $endDate) {
                        $subquery->select(DB::raw(1))
                                 ->from('absensis')
                                 ->whereColumn('absensis.karyawan_id', 'k.id')
                                 ->whereBetween('absensis.tanggal', [$startDate, $endDate]);
                    })
                    ->orWhereExists(function($subquery) use ($startDate, $endDate) {
                        $subquery->select(DB::raw(1))
                                 ->from('izin_presensi')
                                 ->whereColumn('izin_presensi.karyawan_id', 'k.id')
                                 ->where(function($q) use ($startDate, $endDate) {
                                     $q->whereBetween('tanggal_awal', [$startDate, $endDate])
                                       ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                                       ->orWhere(function($q2) use ($startDate, $endDate) {
                                           $q2->where('tanggal_awal', '<=', $startDate)
                                              ->where('tanggal_akhir', '>=', $endDate);
                                       });
                                 });
                    });
                })
                ->count();
            
            // Hitung tidak masuk (estimasi) - hanya untuk karyawan yang punya aktivitas
            $totalHariKerjaSemua = $karyawanDenganAktivitas * $hariKerja;
            $tidakMasuk = max(0, $totalHariKerjaSemua - ($stats->total_hadir ?? 0) - $izinHari);
                
            $data[] = [
                'bulan' => Carbon::create()->month($bulan)->translatedFormat('M'),
                'hadir' => $stats->total_hadir ?? 0,
                'terlambat' => $stats->total_terlambat ?? 0,
                'tepat_waktu' => $stats->total_tepat_waktu ?? 0,
                'izin' => $izinHari,
                'tidak_masuk' => $tidakMasuk
            ];
        }
        
        return $data;
    }
    
    private function getTopKaryawanPunctual($bulan, $tahun, $limit = 10)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        // Hitung jumlah hari kerja dalam bulan
        $tanggalKerja = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            if (!$current->isWeekend()) {
                $tanggalKerja[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        $hariKerja = count($tanggalKerja);
        
        // Ambil semua karyawan aktif
        $karyawanList = DB::table('karyawans')
            ->select(['id', 'nama', 'departemen', 'is_ob'])
            ->where('status', 'aktif')
            ->get();
        
        $result = $karyawanList->map(function($karyawan) use ($startDate, $endDate, $tanggalKerja, $hariKerja) {
            // Ambil semua tanggal yang di-izin
            $izinDates = [];
            $izinRecords = DB::table('izin_presensi')
                ->where('karyawan_id', $karyawan->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('tanggal_awal', '<=', $startDate)
                                ->where('tanggal_akhir', '>=', $endDate);
                          });
                })
                ->get();
            
            // Generate semua tanggal yang di-izin
            foreach($izinRecords as $izin) {
                $izinStart = max($startDate, Carbon::parse($izin->tanggal_awal));
                $izinEnd = min($endDate, Carbon::parse($izin->tanggal_akhir));
                
                $current = $izinStart->copy();
                while($current <= $izinEnd) {
                    if(!$current->isWeekend()) {
                        $izinDates[] = $current->format('Y-m-d');
                    }
                    $current->addDay();
                }
            }
            $izinDates = array_unique($izinDates);
            
            // Hitung kehadiran dan punctuality hanya untuk hari yang TIDAK di-izin
            $tanggalKerjaTanpaIzin = array_diff($tanggalKerja, $izinDates);
            $hariKerjaTanpaIzin = count($tanggalKerjaTanpaIzin);
            
            $totalHadir = 0;
            $tepatWaktu = 0;
            $totalIzin = count($izinDates);
            
            if(!empty($tanggalKerjaTanpaIzin)) {
                // Ambil data absensi untuk tanggal yang tidak di-izin
                $absensiData = DB::table('absensis')
                    ->where('karyawan_id', $karyawan->id)
                    ->whereIn('tanggal', $tanggalKerjaTanpaIzin)
                    ->get();
                
                // Filter data yang valid berdasarkan jenis karyawan
                $validAbsensi = $this->filterValidAbsensi($absensiData, $karyawan);
                
                $totalHadir = $validAbsensi->count();
                
                // Hitung tepat waktu berdasarkan jenis karyawan
                foreach($validAbsensi as $absensi) {
                    if($karyawan->is_ob) {
                        // Untuk OB: tepat waktu = ada jam masuk dan pulang (sudah difilter di atas)
                        $tepatWaktu++;
                    } else {
                        // Untuk non-OB: tepat waktu = tidak ada keterlambatan (late_minutes = 0 atau null)
                        if(!$absensi->late_minutes || $absensi->late_minutes == 0) {
                            $tepatWaktu++;
                        }
                    }
                }
            }
            
            // PERBAIKAN: Perhitungan berdasarkan hari kerja yang seharusnya
            $karyawan->total_hadir = $totalHadir;
            $karyawan->total_izin = $totalIzin;
            $karyawan->tepat_waktu = $tepatWaktu;
            $karyawan->hari_kerja_tanpa_izin = $hariKerjaTanpaIzin;
            $karyawan->total_hari_kerja = $hariKerja;
            
            // Attendance Rate: (hadir + izin) / total hari kerja
            $karyawan->attendance_rate = $hariKerja > 0 ? 
                round((($totalHadir + $totalIzin) / $hariKerja) * 100, 2) : 0;
                
            // Punctuality Rate: tepat waktu / hari kerja tanpa izin (bukan dari total hadir)
            $karyawan->punctuality_rate = $hariKerjaTanpaIzin > 0 ? 
                round(($tepatWaktu / $hariKerjaTanpaIzin) * 100, 2) : 0;
                
            // Composite Score: Gabungan attendance (60%) + punctuality (40%)
            $karyawan->composite_score = ($karyawan->attendance_rate * 0.6) + ($karyawan->punctuality_rate * 0.4);
            
            return $karyawan;
        })
        ->filter(function($karyawan) {
            // Filter: minimal 60% attendance dan minimal 5 hari kerja tanpa izin
            return $karyawan->attendance_rate >= 60 && $karyawan->hari_kerja_tanpa_izin >= 5;
        })
        ->sortByDesc('composite_score') // Sort by composite score instead of punctuality only
        ->take($limit)
        ->values();
        
        return $result;
    }

    /**
     * Get top karyawan dengan penalty terbanyak (paling tidak disiplin)
     */
    private function getTopKaryawanPenalty($bulan, $tahun, $limit = 10)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        // Generate semua tanggal kerja (non-weekend) dalam bulan
        $tanggalKerja = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            if (!$current->isWeekend()) {
                $tanggalKerja[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        
        $karyawanList = DB::table('karyawans')
            ->select(['id', 'nama', 'departemen', 'is_ob'])
            ->where('status', 'aktif')
            ->get();
        
        $result = $karyawanList->map(function($karyawan) use ($startDate, $endDate, $tanggalKerja) {
            // Ambil semua tanggal yang di-izin
            $izinDates = [];
            $izinRecords = DB::table('izin_presensi')
                ->where('karyawan_id', $karyawan->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('tanggal_awal', '<=', $startDate)
                                ->where('tanggal_akhir', '>=', $endDate);
                          });
                })
                ->get();
            
            // Generate semua tanggal yang di-izin
            foreach($izinRecords as $izin) {
                $izinStart = max($startDate, Carbon::parse($izin->tanggal_awal));
                $izinEnd = min($endDate, Carbon::parse($izin->tanggal_akhir));
                
                $current = $izinStart->copy();
                while($current <= $izinEnd) {
                    if(!$current->isWeekend()) {
                        $izinDates[] = $current->format('Y-m-d');
                    }
                    $current->addDay();
                }
            }
            $izinDates = array_unique($izinDates);
            
            // Hitung penalty hanya untuk hari yang TIDAK di-izin
            $tanggalKerjaTanpaIzin = array_diff($tanggalKerja, $izinDates);
            
            $totalPenalty = 0;
            $totalHadir = 0;
            $totalLateMinutes = 0;
            $totalEarlyMinutes = 0;
            
            if(!empty($tanggalKerjaTanpaIzin)) {
                // Ambil data absensi untuk tanggal yang tidak di-izin
                $absensiData = DB::table('absensis')
                    ->where('karyawan_id', $karyawan->id)
                    ->whereIn('tanggal', $tanggalKerjaTanpaIzin)
                    ->get();
                
                // Filter data yang valid berdasarkan jenis karyawan
                $validAbsensi = $this->filterValidAbsensi($absensiData, $karyawan);
                
                $totalHadir = $validAbsensi->count();
                
                // Hitung total penalty minutes hanya dari data yang valid
                foreach($validAbsensi as $absensi) {
                    $penaltyMinutes = $absensi->penalty_minutes ?? 0;
                    $lateMinutes = $absensi->late_minutes ?? 0;
                    $earlyMinutes = $absensi->early_minutes ?? 0;
                    
                    $totalPenalty += $penaltyMinutes;
                    $totalLateMinutes += $lateMinutes;
                    $totalEarlyMinutes += $earlyMinutes;
                }
            }
            
            // Hitung metrik untuk fair evaluation
            $hariKerjaSetelahIzin = count($tanggalKerjaTanpaIzin);
            $persentaseKehadiran = $hariKerjaSetelahIzin > 0 ? 
                round(($totalHadir / $hariKerjaSetelahIzin) * 100, 2) : 0;
            $avgPenaltyPerDay = $totalHadir > 0 ? 
                round($totalPenalty / $totalHadir, 1) : 0;
            
            // Penalty score: Lower penalty per day = better score
            $maxExpectedPenalty = 120; // Assume max 2 hours penalty per day is worst case
            $penaltyScore = $maxExpectedPenalty > 0 ? 
                max(0, (($maxExpectedPenalty - $avgPenaltyPerDay) / $maxExpectedPenalty) * 100) : 100;
            
            // Composite score untuk penalty ranking (Attendance 50% + Penalty Control 50%)
            $compositeScore = ($persentaseKehadiran * 0.5) + ($penaltyScore * 0.5);
            
            $karyawan->total_hadir = $totalHadir;
            $karyawan->total_penalty_minutes = $totalPenalty;
            $karyawan->total_late_minutes = $totalLateMinutes;
            $karyawan->total_early_minutes = $totalEarlyMinutes;
            $karyawan->hari_kerja_efektif = $hariKerjaSetelahIzin;
            $karyawan->persentase_kehadiran = $persentaseKehadiran;
            $karyawan->avg_penalty_per_day = $avgPenaltyPerDay;
            $karyawan->composite_score = round($compositeScore, 2);
            
            // Convert minutes to hours:minutes format for display
            $karyawan->penalty_hours_display = $this->minutesToHoursDisplay($totalPenalty);
            $karyawan->late_hours_display = $this->minutesToHoursDisplay($totalLateMinutes);
            $karyawan->early_hours_display = $this->minutesToHoursDisplay($totalEarlyMinutes);
            
            return $karyawan;
        })
        ->filter(function($karyawan) {
            // Hanya tampilkan yang minimal 5 hari kerja efektif dan ada penalty
            return $karyawan->hari_kerja_efektif >= 5 && $karyawan->total_penalty_minutes > 0;
        })
        ->sortBy('composite_score') // Sort ascending (worst performers first for penalty ranking)
        ->take($limit)
        ->values();
        
        return $result;
    }

    /**
     * Convert minutes to hours:minutes display format
     */
    private function minutesToHoursDisplay($minutes)
    {
        if ($minutes == 0) return '0m';
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0 && $remainingMinutes > 0) {
            return $hours . 'j ' . $remainingMinutes . 'm';
        } elseif ($hours > 0) {
            return $hours . 'j';
        } else {
            return $remainingMinutes . 'm';
        }
    }
    
    /**
     * Filter data absensi yang valid berdasarkan jenis karyawan dan logika rekap
     * Data valid = yang seharusnya dihitung sebagai "hadir" di dashboard
     */
    private function filterValidAbsensi($absensiData, $karyawan)
    {
        return $absensiData->filter(function($row) use ($karyawan) {
            if ($karyawan->is_ob) {
                // Untuk OB: harus ada jam masuk DAN jam pulang (sesuai logika rekap)
                return !is_null($row->jam_masuk) && !is_null($row->jam_pulang);
            } else {
                // Untuk non-OB: periksa keterangan yang valid terlebih dahulu
                $keterangan = strtolower(trim($row->keterangan ?? ''));
                
                // Jika ada keterangan yang menunjukkan kehadiran valid
                if (in_array($keterangan, ['tepat waktu', 'terlambat', 'pulang cepat'])) {
                    return true;
                }
                
                // Jika keterangan adalah "tidak valid" atau "diluar waktu absen", maka tidak valid
                if (in_array($keterangan, ['tidak valid', 'diluar waktu absen'])) {
                    return false;
                }
                
                // Jika tidak ada keterangan, periksa keberadaan jam masuk dan pulang
                // Sesuai dengan logika rekap: harus ada keduanya untuk dianggap valid
                return !is_null($row->jam_masuk) && !is_null($row->jam_pulang);
            }
        });
    }
    
    private function getTopKaryawanTerlambat($bulan, $tahun, $limit = 10)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        // Hitung jumlah hari kerja dalam bulan
        $tanggalKerja = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            if (!$current->isWeekend()) {
                $tanggalKerja[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        $hariKerja = count($tanggalKerja);
        
        // Ambil semua karyawan non-OB yang aktif
        $karyawanList = DB::table('karyawans')
            ->select(['id', 'nama', 'departemen', 'is_ob'])
            ->where('status', 'aktif')
            ->where('is_ob', 0) // Hanya karyawan non-OB yang bisa terlambat
            ->get();
        
        $result = $karyawanList->map(function($karyawan) use ($startDate, $endDate, $tanggalKerja, $hariKerja) {
            // Ambil semua tanggal yang di-izin
            $izinDates = [];
            $izinRecords = DB::table('izin_presensi')
                ->where('karyawan_id', $karyawan->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('tanggal_awal', '<=', $startDate)
                                ->where('tanggal_akhir', '>=', $endDate);
                          });
                })
                ->get();
            
            // Generate semua tanggal yang di-izin
            foreach($izinRecords as $izin) {
                $izinStart = max($startDate, Carbon::parse($izin->tanggal_awal));
                $izinEnd = min($endDate, Carbon::parse($izin->tanggal_akhir));
                
                $current = $izinStart->copy();
                while($current <= $izinEnd) {
                    if(!$current->isWeekend()) {
                        $izinDates[] = $current->format('Y-m-d');
                    }
                    $current->addDay();
                }
            }
            $izinDates = array_unique($izinDates);
            
            // Hitung kehadiran dan keterlambatan hanya untuk hari yang TIDAK di-izin
            $tanggalKerjaTanpaIzin = array_diff($tanggalKerja, $izinDates);
            
            $totalHadir = 0;
            $totalTerlambat = 0;
            
            if(!empty($tanggalKerjaTanpaIzin)) {
                // Ambil data absensi untuk tanggal yang tidak di-izin
                $absensiData = DB::table('absensis')
                    ->where('karyawan_id', $karyawan->id)
                    ->whereIn('tanggal', $tanggalKerjaTanpaIzin)
                    ->get();
                
                // Filter data yang valid berdasarkan jenis karyawan
                $validAbsensi = $this->filterValidAbsensi($absensiData, $karyawan);
                
                $totalHadir = $validAbsensi->count();
                $totalTerlambat = $validAbsensi->where('late_minutes', '>', 0)->count();
            }
            
            $karyawan->total_hadir = $totalHadir;
            $karyawan->total_terlambat = $totalTerlambat;
            
            // Hitung metrik untuk fair evaluation
            $hariKerjaSetelahIzin = count($tanggalKerjaTanpaIzin);
            $persentaseKehadiran = $hariKerjaSetelahIzin > 0 ? 
                round(($totalHadir / $hariKerjaSetelahIzin) * 100, 2) : 0;
            $persentaseTerlambat = $totalHadir > 0 ? 
                round(($totalTerlambat / $totalHadir) * 100, 2) : 0;
            
            // Late score: Higher percentage = worse score (invert untuk scoring)
            $lateScore = max(0, 100 - $persentaseTerlambat);
            
            // Composite score untuk late ranking (Attendance 40% + Late Frequency 60%)
            $compositeScore = ($persentaseKehadiran * 0.4) + ($lateScore * 0.6);
            
            $karyawan->hari_kerja_efektif = $hariKerjaSetelahIzin;
            $karyawan->persentase_kehadiran = $persentaseKehadiran;
            $karyawan->persentase_terlambat = $persentaseTerlambat;
            $karyawan->composite_score = round($compositeScore, 2);
            
            return $karyawan;
        })
        ->filter(function($karyawan) {
            // Hanya tampilkan yang minimal 5 hari kerja efektif dan ada record terlambat
            return $karyawan->hari_kerja_efektif >= 5 && $karyawan->total_terlambat > 0;
        })
        ->sortBy('composite_score') // Sort ascending (worst performers first for late ranking)
        ->take($limit)
        ->values();
        
        return $result->isEmpty() ? collect() : $result;
    }
    
    private function getTopKaryawanTidakMasuk($bulan, $tahun, $limit = 10)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        // Hitung jumlah hari kerja dalam bulan (tidak termasuk weekend)
        $tanggalKerja = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            if (!$current->isWeekend()) {
                $tanggalKerja[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        
        $hariKerja = count($tanggalKerja);
        
        // Jika tidak ada hari kerja di bulan ini, return empty
        if ($hariKerja == 0) {
            return collect();
        }
        
        // Ambil semua karyawan aktif
        $karyawanList = DB::table('karyawans')
            ->select(['id', 'nama', 'departemen', 'is_ob'])
            ->where('status', 'aktif')
            ->get();
        
        $result = $karyawanList->map(function($karyawan) use ($startDate, $endDate, $tanggalKerja, $hariKerja) {
            // Ambil semua tanggal yang di-izin
            $izinDates = [];
            $izinRecords = DB::table('izin_presensi')
                ->where('karyawan_id', $karyawan->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('tanggal_awal', '<=', $startDate)
                                ->where('tanggal_akhir', '>=', $endDate);
                          });
                })
                ->get();
            
            // Generate semua tanggal yang di-izin
            foreach($izinRecords as $izin) {
                $izinStart = max($startDate, Carbon::parse($izin->tanggal_awal));
                $izinEnd = min($endDate, Carbon::parse($izin->tanggal_akhir));
                
                $current = $izinStart->copy();
                while($current <= $izinEnd) {
                    if(!$current->isWeekend()) {
                        $izinDates[] = $current->format('Y-m-d');
                    }
                    $current->addDay();
                }
            }
            $izinDates = array_unique($izinDates);
            
            // Hitung kehadiran hanya untuk hari yang TIDAK di-izin
            $tanggalKerjaTanpaIzin = array_diff($tanggalKerja, $izinDates);
            
            $totalHadir = 0;
            $totalIzin = count($izinDates);
            
            if(!empty($tanggalKerjaTanpaIzin)) {
                // Ambil data absensi untuk tanggal yang tidak di-izin
                $absensiData = DB::table('absensis')
                    ->where('karyawan_id', $karyawan->id)
                    ->whereIn('tanggal', $tanggalKerjaTanpaIzin)
                    ->get();
                
                // Filter data yang valid berdasarkan jenis karyawan
                $validAbsensi = $this->filterValidAbsensi($absensiData, $karyawan);
                
                $totalHadir = $validAbsensi->count();
            }
            
            // Hitung tidak masuk = hari kerja tanpa izin yang tidak ada absensi
            $totalTidakMasuk = count($tanggalKerjaTanpaIzin) - $totalHadir;
            $totalTidakMasuk = max(0, $totalTidakMasuk);
            
            // Hitung metrik untuk fair evaluation
            $hariKerjaSetelahIzin = count($tanggalKerjaTanpaIzin);
            $persentaseKehadiran = $hariKerjaSetelahIzin > 0 ? 
                round(($totalHadir / $hariKerjaSetelahIzin) * 100, 2) : 0;
            $persentaseTidakMasuk = $hariKerjaSetelahIzin > 0 ? 
                round(($totalTidakMasuk / $hariKerjaSetelahIzin) * 100, 2) : 0;
            
            // Absence score: Higher percentage tidak masuk = worse score (invert untuk scoring)
            $absenceScore = max(0, 100 - $persentaseTidakMasuk);
            
            // Composite score untuk absence ranking (Attendance 60% + Absence Control 40%)
            $compositeScore = ($persentaseKehadiran * 0.6) + ($absenceScore * 0.4);
            
            $karyawan->total_hadir = $totalHadir;
            $karyawan->total_izin = $totalIzin;
            $karyawan->total_tidak_masuk = $totalTidakMasuk;
            $karyawan->hari_kerja_efektif = $hariKerjaSetelahIzin;
            $karyawan->persentase_kehadiran = $persentaseKehadiran;
            $karyawan->persentase_tidak_masuk = $persentaseTidakMasuk;
            $karyawan->composite_score = round($compositeScore, 2);
            
            return $karyawan;
        })
        // Filter hanya yang memiliki minimal 5 hari kerja efektif dan ada aktivitas tidak masuk
        ->filter(function($karyawan) {
            return $karyawan->hari_kerja_efektif >= 5 && $karyawan->total_tidak_masuk > 0;
        })
        // Sort berdasarkan composite score ascending (worst performers first)
        ->sortBy('composite_score')
        ->take($limit)
        ->values(); // Reset array keys
        
        return $result;
    }
    
    private function getStatistikUmum($bulan, $tahun)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        $totalKaryawan = Karyawan::where('status', 'aktif')->count();
        
        // Hitung jumlah hari kerja dalam bulan (tidak termasuk weekend)
        $tanggalKerja = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            if (!$current->isWeekend()) {
                $tanggalKerja[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        $hariKerja = count($tanggalKerja);
        
        // Hitung total berdasarkan semua karyawan
        $totalKehadiran = 0;
        $totalTerlambat = 0;
        $totalTepatWaktu = 0;
        $totalIzin = 0;
        $totalTidakMasuk = 0;
        
        $karyawanList = Karyawan::where('status', 'aktif')->get();
        
        $karyawanDenganAktivitas = 0;
        
        foreach($karyawanList as $karyawan) {
            // Cek apakah karyawan ini punya data absensi atau izin di bulan ini
            $adaAbsensi = DB::table('absensis')
                ->where('karyawan_id', $karyawan->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->exists();
            
            $adaIzin = DB::table('izin_presensi')
                ->where('karyawan_id', $karyawan->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('tanggal_awal', '<=', $startDate)
                                ->where('tanggal_akhir', '>=', $endDate);
                          });
                })
                ->exists();
            
            // Skip karyawan yang tidak ada aktivitas sama sekali di bulan ini
            if (!$adaAbsensi && !$adaIzin) {
                continue;
            }
            
            $karyawanDenganAktivitas++;
            
            // Ambil semua tanggal yang di-izin untuk karyawan ini
            $izinDates = [];
            $izinRecords = DB::table('izin_presensi')
                ->where('karyawan_id', $karyawan->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                          ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('tanggal_awal', '<=', $startDate)
                                ->where('tanggal_akhir', '>=', $endDate);
                          });
                })
                ->get();
            
            // Generate semua tanggal yang di-izin
            foreach($izinRecords as $izin) {
                $izinStart = max($startDate, Carbon::parse($izin->tanggal_awal));
                $izinEnd = min($endDate, Carbon::parse($izin->tanggal_akhir));
                
                $current = $izinStart->copy();
                while($current <= $izinEnd) {
                    if(!$current->isWeekend()) {
                        $izinDates[] = $current->format('Y-m-d');
                    }
                    $current->addDay();
                }
            }
            $izinDates = array_unique($izinDates);
            $totalIzin += count($izinDates); // ✅ Hitung jumlah HARI, bukan record
            
            // Hitung kehadiran hanya untuk hari yang TIDAK di-izin
            $tanggalKerjaTanpaIzin = array_diff($tanggalKerja, $izinDates);
            
            if(!empty($tanggalKerjaTanpaIzin)) {
                // Ambil data absensi untuk tanggal yang tidak di-izin
                $absensiData = DB::table('absensis')
                    ->where('karyawan_id', $karyawan->id)
                    ->whereIn('tanggal', $tanggalKerjaTanpaIzin)
                    ->whereNotNull('jam_masuk')
                    ->get();
                
                $totalKehadiran += $absensiData->count();
                
                // Hitung terlambat dan tepat waktu
                foreach($absensiData as $absensi) {
                    if($karyawan->is_ob) {
                        // Untuk OB, tepat waktu = ada jam masuk dan pulang
                        if($absensi->jam_masuk && $absensi->jam_pulang) {
                            $totalTepatWaktu++;
                        }
                    } else {
                        // Untuk non-OB: berdasarkan late_minutes
                        if($absensi->late_minutes && $absensi->late_minutes > 0) {
                            $totalTerlambat++;
                        } else {
                            $totalTepatWaktu++;
                        }
                    }
                }
                
                // Hitung tidak masuk = hari kerja tanpa izin yang tidak ada absensi
                $hariHadirDiTanggalTanpaIzin = $absensiData->pluck('tanggal')->map(function($date) {
                    return Carbon::parse($date)->format('Y-m-d');
                })->toArray();
                
                $hariTidakMasuk = array_diff($tanggalKerjaTanpaIzin, $hariHadirDiTanggalTanpaIzin);
                $totalTidakMasuk += count($hariTidakMasuk);
            }
        }
        
        return [
            'total_karyawan' => $karyawanDenganAktivitas, // Hanya yang punya aktivitas
            'total_kehadiran' => $totalKehadiran,
            'total_terlambat' => $totalTerlambat,
            'total_tepat_waktu' => $totalTepatWaktu,
            'total_izin' => $totalIzin,
            'total_tidak_masuk' => $totalTidakMasuk,
            'hari_kerja' => $hariKerja,
            'persentase_kehadiran' => ($karyawanDenganAktivitas * $hariKerja) > 0 ? round(($totalKehadiran / ($karyawanDenganAktivitas * $hariKerja)) * 100, 2) : 0
        ];
    }
}
