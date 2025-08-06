<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Absensi;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AbsensiController extends Controller
{
    public function index()
    {
        return view('absensi.index');
    }

    public function preview(Request $request)
    {
        // 1. Validasi input saat POST
        if ($request->isMethod('post')) {
            $request->validate([
                'file_excel.*'          => 'required|mimes:xlsx,xls',
                'jam_masuk_min_senin'   => 'required|date_format:H:i',
                'jam_masuk_max_senin'   => 'required|date_format:H:i',
                'jam_pulang_min_senin'  => 'required|date_format:H:i',
                'jam_pulang_max_senin'  => 'required|date_format:H:i',
                'jam_masuk_min_jumat'   => 'required|date_format:H:i',
                'jam_masuk_max_jumat'   => 'required|date_format:H:i',
                'jam_pulang_min_jumat'  => 'required|date_format:H:i',
                'jam_pulang_max_jumat'  => 'required|date_format:H:i',
            ]);
        }

        // 2. Siapkan container hasil dan catat bulan-tahun
        $preview = [];
        $bulanTahunSet = [];

        // 3. Baca filter jam dari request
        $seninKamis = [
            'masuk_min'  => $request->input('jam_masuk_min_senin',  '07:00'),
            'masuk_max'  => $request->input('jam_masuk_max_senin',  '07:30'),
            'pulang_min' => $request->input('jam_pulang_min_senin', '15:30'),
            'pulang_max' => $request->input('jam_pulang_max_senin', '17:00'),
        ];
        $jumat = [
            'masuk_min'  => $request->input('jam_masuk_min_jumat',  '07:00'),
            'masuk_max'  => $request->input('jam_masuk_max_jumat',  '07:30'),
            'pulang_min' => $request->input('jam_pulang_min_jumat', '15:00'),
            'pulang_max' => $request->input('jam_pulang_max_jumat', '17:00'),
        ];

        // 4. Jika ada file Excel, parse semuanya
        if ($request->hasFile('file_excel')) {
            foreach ($request->file('file_excel') as $file) {
                $data = Excel::toArray([], $file->getRealPath());
                $sheet = $data[2] ?? [];        // sheet ke-3
                $barisTanggal = $sheet[3] ?? [];      // row ke-4 sebagai header tanggal

                // 4a. Parse rentang tanggal dari C3
                $cellC3 = $sheet[2][2] ?? null;
                if (! $cellC3) {
                    return back()->with('error', 'Cell C3 tidak ditemukan.');
                }
                if (is_string($cellC3) && str_contains($cellC3, '~')) {
                    [$rawAwal, $rawAkhir] = array_map('trim', explode('~', $cellC3));
                    $startDate = Carbon::parse($rawAwal);
                    $endDate = Carbon::parse($rawAkhir);
                } elseif (is_numeric($cellC3)) {
                    $dt = Date::excelToDateTimeObject($cellC3);
                    $startDate = Carbon::instance($dt);
                    $endDate = Carbon::instance((clone $dt));
                } else {
                    $startDate = Carbon::parse($cellC3);
                    $endDate = Carbon::parse($cellC3);
                }
                $tahun = $startDate->year;
                $bulan = $startDate->month;
                $bulanTahunSet[] = sprintf('%04d-%02d', $tahun, $bulan);

                // 4b. Loop baris data (dua-dua: infoRow & dataRow)
                for ($i = 4; $i < count($sheet); $i += 2) {
                    $infoRow = $sheet[$i];
                    $dataRow = $sheet[$i + 1] ?? [];

                    $nama       = $infoRow[10] ?? null;
                    $departemen = $infoRow[20] ?? null;
                    if (! $nama || ! $departemen) continue;

                    // 4c. Untuk setiap kolom tanggal 1–30
                    for ($col = 1; $col <= 31; $col++) {
                        $tanggalKe = $barisTanggal[$col] ?? null;
                        $raw       = $dataRow[$col] ?? null;
                        if (! $tanggalKe || ! $raw) continue;

                        // parse satu atau beberapa jam
                        $jamList = [];
                        if (is_numeric($raw)) {
                            try {
                                $dt2 = Date::excelToDateTimeObject($raw);
                                $jamList[] = Carbon::instance($dt2)->format('H:i');
                            } catch (\Exception $e) {
                                continue;
                            }
                        } elseif (is_string($raw)) {
                            preg_match_all('/\d{1,2}:\d{2}/', $raw, $m);
                            $jamList = $m[0] ?? [];
                        }

                        if (empty($jamList)) continue;
                        
                        // bangun tanggal lengkap
                        $tanggal = Carbon::createFromDate($tahun, $bulan, (int)$tanggalKe)
                                         ->format('Y-m-d');
                        // jika di luar rentang C3, skip
                        if (Carbon::parse($tanggal)->lt($startDate)
                           || Carbon::parse($tanggal)->gt($endDate)) {
                            continue;
                        }

                        // pilih range berdasar hari kerja / Jumat
                        $dow = Carbon::parse($tanggal)->dayOfWeekIso;
                        if ($dow >= 1 && $dow <= 4) {
                            $range = $seninKamis;
                        } elseif ($dow === 5) {
                            $range = $jumat;
                        } else {
                            continue; // weekend
                        }

                        // buat objek batas masuk/pulang
                        $masukMin  = Carbon::createFromFormat('H:i', $range['masuk_min']);
                        $masukMax  = Carbon::createFromFormat('H:i', $range['masuk_max']);
                        $pulangMin = Carbon::createFromFormat('H:i', $range['pulang_min']);
                        $pulangMax = Carbon::createFromFormat('H:i', $range['pulang_max']);
                        
                        // 4d. Tentukan jam masuk dan jam pulang yang paling valid dari semua jam yang tersedia
                        $jamMasukValid = null;
                        $jamPulangValid = null;

                        // Urutkan jam dari yang paling awal
                        sort($jamList);

                        // Cari jam masuk (paling awal yang valid)
                        foreach ($jamList as $j) {
                            $jamObj = Carbon::createFromFormat('H:i', $j);
                            if ($jamObj->betweenIncluded($masukMin, $masukMax)) {
                                $jamMasukValid = $j;
                                break; // Ambil jam masuk pertama yang valid
                            }
                        }
                        
                        // Jika jam masuk tidak ditemukan, ambil jam pertama (paling awal) dari semua data
                        if (!$jamMasukValid && !empty($jamList)) {
                            // Dengan asumsi jam paling awal selalu jam masuk jika tidak ada yang valid
                            $jamMasukValid = $jamList[0];
                        }
                        
                        // Cari jam pulang (paling akhir yang valid)
                        // Periksa dari belakang array $jamList yang sudah diurutkan
                        $reversedJamList = array_reverse($jamList);
                        foreach ($reversedJamList as $j) {
                            $jamObj = Carbon::createFromFormat('H:i', $j);
                            // Pastikan jam pulang lebih besar dari jam masuk
                            if ($jamMasukValid && $j > $jamMasukValid) {
                                if ($jamObj->betweenIncluded($pulangMin, $pulangMax)) {
                                    $jamPulangValid = $j;
                                    break; // Ambil jam pulang pertama (paling akhir) yang valid
                                }
                            }
                        }
                        // Jika jam pulang tidak ditemukan, ambil jam terakhir dari semua data
                        if (!$jamPulangValid && !empty($jamList) && count($jamList) > 1 && $jamList[count($jamList) - 1] > $jamMasukValid) {
                            // Dengan asumsi jam paling akhir selalu jam pulang jika tidak ada yang valid
                            $jamPulangValid = $jamList[count($jamList) - 1];
                        }

                        // Tambahkan entri tunggal per hari dengan jam yang sudah divalidasi
                        // Hitung satu kolom keterangan dengan override
                        $keterangan = null;
                        
                        // Jika jam masuk dan jam pulang tidak ada, tidak perlu diproses
                        if (! $jamMasukValid && ! $jamPulangValid) {
                            continue;
                        }

                        $jm = $jamMasukValid ? Carbon::createFromFormat('H:i', $jamMasukValid) : null;
                        $jp = $jamPulangValid ? Carbon::createFromFormat('H:i', $jamPulangValid) : null;

                        // Kasus 2: Hanya ada jam masuk, tidak ada jam pulang
                        if ($jm && !$jp) {
                            $keterangan = 'terlambat';
                        } 
                        // Kasus 3: Hanya ada jam pulang, tidak ada jam masuk
                        else if (!$jm && $jp) {
                            $keterangan = 'terlambat';
                        }           
                        // Kasus 4: Ada jam masuk dan jam pulang
                        else if ($jm && $jp) {
                            $sMasuk = null;
                            if ($jm->lt($masukMin))         $sMasuk = 'diluar waktu absen';
                            elseif ($jm->gt($masukMax))     $sMasuk = 'terlambat';
                            else                            $sMasuk = 'tepat waktu';
                            
                            $sPulang = null;
                            if ($jp->gt($pulangMax))        $sPulang = 'diluar waktu absen';
                            elseif ($jp->lt($pulangMin))    $sPulang = 'terlambat';
                            else                            $sPulang = 'tepat waktu';

                            $all = array_filter([$sMasuk, $sPulang]);
                            if (in_array('diluar waktu absen', $all))   $keterangan = 'diluar waktu absen';
                            elseif (in_array('terlambat', $all))        $keterangan = 'terlambat';
                            else                                        $keterangan = 'tepat waktu';
                        }

                        
                        $preview[] = [
                            'nama'       => $nama,
                            'departemen' => $departemen,
                            'tanggal'    => $tanggal,
                            'jam_masuk'  => $jamMasukValid,
                            'jam_pulang' => $jamPulangValid,
                            'keterangan' => $keterangan,
                        ];
                    }
                }
            }

            // 5. Validasi bulan sama & hapus data lama
            $bulanUnik = array_unique($bulanTahunSet);
            if (count($bulanUnik) > 1) {
                return back()->with('error', 'Bulan tidak sama antara file!');
            }
            [$y, $m] = explode('-', $bulanUnik[0]);
            Absensi::whereYear('tanggal', $y)
                ->whereMonth('tanggal', $m)
                ->delete();

            // 6. Simpan preview ke session
            session(['preview_data' => $preview]);
        } else {
            // GET: ambil dari session
            $preview = session('preview_data', []);
        }
        
        // 7. Jika kosong
        if (count($preview) === 0) {
            return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
        }

        // 8. Filter & Sort
        $collection = collect($preview);
        if ($search = $request->input('search')) {
            $collection = $collection->filter(fn($row) =>
                stripos($row['nama'], $search) !== false
            );
        }
        switch ($request->input('sort_by')) {
            case 'nama_asc':    $collection = $collection->sortBy('nama');      break;
            case 'nama_desc':   $collection = $collection->sortByDesc('nama');   break;
            case 'tanggal_asc': $collection = $collection->sortBy('tanggal');    break;
            case 'tanggal_desc':$collection = $collection->sortByDesc('tanggal');break;
        }

        // 9. Paginasi
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedPreview = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 10. Kembalikan view
        return view('absensi.index', [
            'preview' => $paginatedPreview,
        ]);
    }
    
    public function store(Request $request)
    {
        $data = session('preview_data');

        if (! is_array($data) || empty($data)) {
            return back()->with('error', 'Tidak ada data yang bisa disimpan.');
        }

        foreach ($data as $row) {
            // 1) Ambil atau buat karyawan
            $karyawan = Karyawan::firstOrCreate([
                'nama'       => $row['nama'],
                'departemen' => $row['departemen'],
            ]);

            // 2) Replace/insert Absensi per karyawan + tanggal
            Absensi::updateOrCreate(
                [
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $row['tanggal'],
                ],
                [
                    'jam_masuk'  => $row['jam_masuk'],
                    'jam_pulang' => $row['jam_pulang'],
                    'keterangan' => $row['keterangan'] ?? null,
                ]
            );
        }

        // 3) Clear session dan redirect
        session()->forget('preview_data');

        return redirect()->route('absensi.index')
                         ->with('success', 'Semua data absensi berhasil disimpan!');
    }
}