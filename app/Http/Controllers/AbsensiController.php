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
        // Debug: Log input Ramadhan
        if ($request->isMethod('post')) {
            $debugInfo = [
                'ramadhan_start_date' => $request->input('ramadhan_start_date'),
                'ramadhan_end_date' => $request->input('ramadhan_end_date'),
                'jam_masuk_min_ramadhan_senin' => $request->input('jam_masuk_min_ramadhan_senin'),
                'jam_masuk_max_ramadhan_senin' => $request->input('jam_masuk_max_ramadhan_senin'),
            ];
            \Log::info('Ramadhan Debug:', $debugInfo);
            
            // Temporary debug - comment out after testing
            if ($request->input('ramadhan_start_date') && $request->input('ramadhan_end_date')) {
                session()->flash('debug_ramadhan', 'Ramadhan dates received: ' . 
                    $request->input('ramadhan_start_date') . ' to ' . 
                    $request->input('ramadhan_end_date'));
            }
        }

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
                // Validasi field Ramadhan - boleh kosong
                'ramadhan_start_date'   => 'nullable|date',
                'ramadhan_end_date'     => 'nullable|date',
                'jam_masuk_min_ramadhan_senin'   => 'nullable|date_format:H:i',
                'jam_masuk_max_ramadhan_senin'   => 'nullable|date_format:H:i',
                'jam_pulang_min_ramadhan_senin'  => 'nullable|date_format:H:i',
                'jam_pulang_max_ramadhan_senin'  => 'nullable|date_format:H:i',
                'jam_masuk_min_ramadhan_jumat'   => 'nullable|date_format:H:i',
                'jam_masuk_max_ramadhan_jumat'   => 'nullable|date_format:H:i',
                'jam_pulang_min_ramadhan_jumat'  => 'nullable|date_format:H:i',
                'jam_pulang_max_ramadhan_jumat'  => 'nullable|date_format:H:i',
            ]);
        }

        // 2. Siapkan container hasil dan catat bulan-tahun
        $preview = [];
        $bulanTahunSet = [];

        // 3. Baca filter jam dari request (non-Ramadhan)
        $seninKamis = [
            'masuk_min'  => $request->input('jam_masuk_min_senin', '07:00'),
            'masuk_max'  => $request->input('jam_masuk_max_senin', '07:30'),
            'pulang_min' => $request->input('jam_pulang_min_senin', '15:30'),
            'pulang_max' => $request->input('jam_pulang_max_senin', '17:00'),
        ];
        $jumat = [
            'masuk_min'  => $request->input('jam_masuk_min_jumat', '07:00'),
            'masuk_max'  => $request->input('jam_masuk_max_jumat', '07:30'),
            'pulang_min' => $request->input('jam_pulang_min_jumat', '15:00'),
            'pulang_max' => $request->input('jam_pulang_max_jumat', '17:00'),
        ];

        // 4. Ambil rentang & konfigurasi Ramadhan jika ada
        $ramadhanStartDate = $request->input('ramadhan_start_date');
        $ramadhanEndDate = $request->input('ramadhan_end_date');
        $ramadhanRange = null;
        
        if ($ramadhanStartDate && $ramadhanEndDate) {
            try {
                $startDate = Carbon::parse($ramadhanStartDate);
                $endDate   = Carbon::parse($ramadhanEndDate);

                // Debug: Log parsed dates
                \Log::info('Ramadhan Dates Parsed:', [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ]);

                $ramadhanRange = [
                    'start_date'   => $startDate,
                    'end_date'     => $endDate,
                    'senin_kamis' => [
                        'masuk_min'  => $request->input('jam_masuk_min_ramadhan_senin', '08:00'),
                        'masuk_max'  => $request->input('jam_masuk_max_ramadhan_senin', '08:30'),
                        'pulang_min' => $request->input('jam_pulang_min_ramadhan_senin', '15:00'),
                        'pulang_max' => $request->input('jam_pulang_max_ramadhan_senin', '16:00'),
                    ],
                    'jumat' => [
                        'masuk_min'  => $request->input('jam_masuk_min_ramadhan_jumat', '08:00'),
                        'masuk_max'  => $request->input('jam_masuk_max_ramadhan_jumat', '08:30'),
                        'pulang_min' => $request->input('jam_pulang_min_ramadhan_jumat', '15:00'),
                        'pulang_max' => $request->input('jam_pulang_max_ramadhan_jumat', '16:00'),
                    ],
                ];
                
                // Debug: Log Ramadhan config
                \Log::info('Ramadhan Config:', $ramadhanRange);
                
            } catch (\Exception $e) {
                return back()->with('error', 'Format tanggal Ramadhan tidak valid: ' . $e->getMessage());
            }
        }

        // 5. Jika ada file Excel, parse semuanya
        if ($request->hasFile('file_excel')) {
            foreach ($request->file('file_excel') as $file) {
                $data = Excel::toArray(new \stdClass(), $file->getRealPath());
                $sheet = $data[2] ?? [];           // sheet ke-3
                $barisTanggal = $sheet[3] ?? [];   // row ke-4 sebagai header tanggal

                // 5a. Parse rentang tanggal dari C3
                $cellC3 = $sheet[2][2] ?? null;
                if (! $cellC3) {
                    return back()->with('error', 'Cell C3 tidak ditemukan.');
                }
                if (is_string($cellC3) && str_contains($cellC3, '~')) {
                    [$rawAwal, $rawAkhir] = array_map('trim', explode('~', $cellC3));
                    $startDate = Carbon::parse($rawAwal);
                    $endDate   = Carbon::parse($rawAkhir);
                } elseif (is_numeric($cellC3)) {
                    $dt = Date::excelToDateTimeObject($cellC3);
                    $startDate = Carbon::instance($dt);
                    $endDate   = Carbon::instance((clone $dt));
                } else {
                    $startDate = Carbon::parse($cellC3);
                    $endDate   = Carbon::parse($cellC3);
                }
                $tahun = $startDate->year;
                $bulan = $startDate->month;
                $bulanTahunSet[] = sprintf('%04d-%02d', $tahun, $bulan);

                // 5b. Loop baris data (infoRow & dataRow)
                for ($i = 4; $i < count($sheet); $i += 2) {
                    $infoRow = $sheet[$i];
                    $dataRow = $sheet[$i + 1] ?? [];

                    $nama       = $infoRow[10] ?? null;
                    $departemen = $infoRow[20] ?? null;
                    if (! $nama || ! $departemen) continue;

                    // 5c. Untuk setiap tanggal 1–31
                    for ($col = 1; $col <= 31; $col++) {
                        $tanggalKe = $barisTanggal[$col] ?? null;
                        $raw       = $dataRow[$col]      ?? null;
                        if (! $tanggalKe || ! $raw) continue;

                        // parse timestamp/string ke daftar jam
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

                        // bangun tanggal penuh dan cek C3-range
                        $tgl = Carbon::createFromDate($tahun, $bulan, (int)$tanggalKe)->format('Y-m-d');
                        if (Carbon::parse($tgl)->lt($startDate)
                         || Carbon::parse($tgl)->gt($endDate)) {
                            continue;
                        }

                        // pilih konfigurasi jam berdasarkan:
                        // - Ramadhan (dalam rentang + beda Jumat vs Senin–Kamis)
                        // - atau non-Ramadhan (seninKamis/jumat biasa)
                        $tanggalObj = Carbon::parse($tgl);
                        $isRamadhan = false;
                        
                        if ($ramadhanRange
                            && $tanggalObj->between(
                                $ramadhanRange['start_date'],
                                $ramadhanRange['end_date']
                            )
                        ) {
                            // Ramadhan
                            $isRamadhan = true;
                            if ($tanggalObj->dayOfWeekIso === 5) { // Jumat
                                $range = $ramadhanRange['jumat'];
                                \Log::info('Using Ramadhan Jumat rules for: ' . $tgl);
                            } else { // Senin-Kamis
                                $range = $ramadhanRange['senin_kamis'];
                                \Log::info('Using Ramadhan Senin-Kamis rules for: ' . $tgl);
                            }
                        } else {
                            // non-Ramadhan
                            $dow = $tanggalObj->dayOfWeekIso;
                            if ($dow >= 1 && $dow <= 4) { // Senin-Kamis
                                $range = $seninKamis;
                            } elseif ($dow === 5) { // Jumat
                                $range = $jumat;
                            } else {
                                continue; // weekend, skip
                            }
                        }

                        // buat objek Carbon untuk batas waktu
                        $masukMin  = Carbon::createFromFormat('H:i', $range['masuk_min']);
                        $masukMax  = Carbon::createFromFormat('H:i', $range['masuk_max']);
                        $pulangMin = Carbon::createFromFormat('H:i', $range['pulang_min']);
                        $pulangMax = Carbon::createFromFormat('H:i', $range['pulang_max']);

                        // 5d. Tentukan jam masuk/pulang valid
                        sort($jamList);
                        $jamMasukValid  = null;
                        $jamPulangValid = null;

                        // cari jam masuk valid
                        foreach ($jamList as $j) {
                            $jObj = Carbon::createFromFormat('H:i', $j);
                            if ($jObj->betweenIncluded($masukMin, $masukMax)) {
                                $jamMasukValid = $j;
                                break;
                            }
                        }
                        if (!$jamMasukValid) {
                            $jamMasukValid = $jamList[0];
                        }

                        // cari jam pulang valid
                        foreach (array_reverse($jamList) as $j) {
                            $jObj = Carbon::createFromFormat('H:i', $j);
                            if ($jamMasukValid && $j > $jamMasukValid
                             && $jObj->betweenIncluded($pulangMin, $pulangMax)
                            ) {
                                $jamPulangValid = $j;
                                break;
                            }
                        }
                        if (!$jamPulangValid && count($jamList) > 1
                            && end($jamList) > $jamMasukValid
                        ) {
                            $jamPulangValid = end($jamList);
                        }

                        if (! $jamMasukValid && ! $jamPulangValid) {
                            continue;
                        }

                        // keterangan terlambat/tepat waktu/diluar
                        $jm = $jamMasukValid ? Carbon::createFromFormat('H:i', $jamMasukValid) : null;
                        $jp = $jamPulangValid ? Carbon::createFromFormat('H:i', $jamPulangValid) : null;
                        $keterangan = null;

                        if ($jm && !$jp) {
                            $keterangan = 'terlambat';
                        } elseif (!$jm && $jp) {
                            $keterangan = 'terlambat';
                        } elseif ($jm && $jp) {
                            $sMasuk  = $jm->lt($masukMin)
                                        ? 'diluar waktu absen'
                                        : ($jm->gt($masukMax) ? 'terlambat' : 'tepat waktu');
                            $sPulang = $jp->gt($pulangMax)
                                        ? 'diluar waktu absen'
                                        : ($jp->lt($pulangMin) ? 'terlambat' : 'tepat waktu');
                            $all = array_filter([$sMasuk, $sPulang]);
                            if (in_array('diluar waktu absen', $all)) {
                                $keterangan = 'diluar waktu absen';
                            } elseif (in_array('terlambat', $all)) {
                                $keterangan = 'terlambat';
                            } else {
                                $keterangan = 'tepat waktu';
                            }
                        }

                        // Tambahkan info Ramadhan ke keterangan untuk debugging
                        // if ($isRamadhan) {
                        //     $keterangan = $keterangan . ' (Ramadhan)';
                        // }

                        $preview[] = [
                            'nama'       => $nama,
                            'departemen' => $departemen,
                            'tanggal'    => $tgl,
                            'jam_masuk'  => $jamMasukValid,
                            'jam_pulang' => $jamPulangValid,
                            'keterangan' => $keterangan,
                        ];
                    }
                }
            }

            // 6. Validasi bulan sama & hapus data lama
            $bulanUnik = array_unique($bulanTahunSet);
            if (count($bulanUnik) > 1) {
                return back()->with('error', 'Bulan tidak sama antara file!');
            }
            [$y, $m] = explode('-', $bulanUnik[0]);
            Absensi::whereYear('tanggal', $y)
                   ->whereMonth('tanggal', $m)
                   ->delete();

            // 7. Simpan preview ke session
            session(['preview_data' => $preview]);
        } else {
            // GET: ambil dari session
            $preview = session('preview_data', []);
        }

        // 8. Jika kosong
        if (count($preview) === 0) {
            return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
        }

        // 9. Filter & Sort
        $collection = collect($preview);
        if ($search = $request->input('search')) {
            $collection = $collection->filter(fn($row) =>
                stripos($row['nama'], $search) !== false
            );
        }
        switch ($request->input('sort_by')) {
            case 'nama_asc':    $collection = $collection->sortBy('nama');      break;
            case 'nama_desc':   $collection = $collection->sortByDesc('nama');  break;
            case 'tanggal_asc': $collection = $collection->sortBy('tanggal');    break;
            case 'tanggal_desc':$collection = $collection->sortByDesc('tanggal');break;
        }

        // 10. Paginasi
        $currentPage   = LengthAwarePaginator::resolveCurrentPage();
        $perPage       = 25;
        $currentItems  = $collection
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $paginatedPreview = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 11. Kembalikan view
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
            $karyawan = Karyawan::firstOrCreate([
                'nama'       => $row['nama'],
                'departemen' => $row['departemen'],
            ]);

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

        session()->forget('preview_data');

        return redirect()
            ->route('absensi.index')
            ->with('success', 'Semua data absensi berhasil disimpan!');
    }
}
