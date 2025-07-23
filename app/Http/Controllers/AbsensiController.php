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
        if ($request->isMethod('post')) {
            $request->validate([
                'file_excel.*'                  => 'required|mimes:xlsx,xls',
                'jam_masuk_min_senin'           => 'required|date_format:H:i',
                'jam_masuk_max_senin'           => 'required|date_format:H:i',
                'jam_pulang_min_senin'          => 'required|date_format:H:i',
                'jam_pulang_max_senin'          => 'required|date_format:H:i',
                'jam_masuk_min_jumat'           => 'required|date_format:H:i',
                'jam_masuk_max_jumat'           => 'required|date_format:H:i',
                'jam_pulang_min_jumat'          => 'required|date_format:H:i',
                'jam_pulang_max_jumat'          => 'required|date_format:H:i',
            ]);
        }

        $preview = [];

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

        if ($request->hasFile('file_excel')) {
            foreach ($request->file('file_excel') as $file) {
                $path  = $file->getRealPath();
                $data  = Excel::toArray([], $path);
                $sheet = $data[2] ?? [];            // sheet ke-3
                $barisTanggal = $sheet[3] ?? [];   // row ke-4 sebagai header tanggal

                for ($i = 4; $i < count($sheet); $i += 2) {
                    $infoRow = $sheet[$i];
                    $dataRow = $sheet[$i + 1] ?? [];

                    $nama       = $infoRow[10] ?? null;
                    $departemen = $infoRow[20] ?? null;
                    if (!$nama || !$departemen) {
                        continue;
                    }

                    for ($col = 1; $col <= 30; $col++) {
                        $tanggalKe = $barisTanggal[$col] ?? null;
                        if (!$tanggalKe) {
                            continue;
                        }

                        $raw = $dataRow[$col] ?? null;
                        if (!$raw) {
                            continue;
                        }

                        // parsing jam dari cell (numeric or string)
                        $jamList = [];
                        if (is_numeric($raw)) {
                            try {
                                $dt = Date::excelToDateTimeObject($raw);
                                $jamList[] = $dt->format('H:i');
                            } catch (\Exception $e) {
                                continue;
                            }
                        } elseif (is_string($raw)) {
                            preg_match_all('/\d{1,2}:\d{2}/', $raw, $m);
                            $jamList = $m[0] ?? [];
                        } else {
                            continue;
                        }
                        // end parsing

                        $tanggal = sprintf('2025-04-%02d', (int) $tanggalKe);
                        $dow     = Carbon::parse($tanggal)->dayOfWeekIso;
                        if ($dow >= 1 && $dow <= 4) {
                            $range = $seninKamis;
                        } elseif ($dow === 5) {
                            $range = $jumat;
                        } else {
                            continue; // skip weekend
                        }

                        $masukMin  = Carbon::createFromFormat('H:i', $range['masuk_min']);
                        $masukMax  = Carbon::createFromFormat('H:i', $range['masuk_max']);
                        $pulangMin = Carbon::createFromFormat('H:i', $range['pulang_min']);
                        $pulangMax = Carbon::createFromFormat('H:i', $range['pulang_max']);

foreach ($jamList as $j) {
    $j      = trim($j);
    $jamObj = Carbon::createFromFormat('H:i', $j);
    $pushed = false;

    // 1) Masuk kalau dalam window masuk
    if ($jamObj->betweenIncluded($masukMin, $masukMax)) {
        $preview[] = [
            'nama'       => $nama,
            'departemen' => $departemen,
            'tanggal'    => $tanggal,
            'jam_masuk'  => $j,
            'jam_pulang' => null,
        ];
        $pushed = true;
    }

    // 2) Pulang kalau dalam window pulang
    if ($jamObj->betweenIncluded($pulangMin, $pulangMax)) {
        $preview[] = [
            'nama'       => $nama,
            'departemen' => $departemen,
            'tanggal'    => $tanggal,
            'jam_masuk'  => null,
            'jam_pulang' => $j,
        ];
        $pushed = true;
    }

    // 3) Kalau tidak masuk kedua window, tetapkan sendiri:
    //    sebelum jam pulang_min → anggap masuk, 
    //    sesudahnya → anggap pulang
    if (! $pushed) {
        if ($jamObj->lt($pulangMin)) {
            $preview[] = [
                'nama'       => $nama,
                'departemen' => $departemen,
                'tanggal'    => $tanggal,
                'jam_masuk'  => $j,
                'jam_pulang' => null,
            ];
        } else {
            $preview[] = [
                'nama'       => $nama,
                'departemen' => $departemen,
                'tanggal'    => $tanggal,
                'jam_masuk'  => null,
                'jam_pulang' => $j,
            ];
        }
    }
}

                    }
                }
            }

            // --- MERGE jam_masuk & jam_pulang per (nama, departemen, tanggal) ---
            $merged = [];
            foreach ($preview as $row) {
                $key = "{$row['nama']}|{$row['departemen']}|{$row['tanggal']}";
                if (!isset($merged[$key])) {
                    $merged[$key] = [
                        'nama'       => $row['nama'],
                        'departemen' => $row['departemen'],
                        'tanggal'    => $row['tanggal'],
                        'jam_masuk'  => null,
                        'jam_pulang' => null,
                    ];
                }
                if (!empty($row['jam_masuk'])) {
                    $merged[$key]['jam_masuk'] = $row['jam_masuk'];
                }
                if (!empty($row['jam_pulang'])) {
                    $merged[$key]['jam_pulang'] = $row['jam_pulang'];
                }
            }

// HITUNG KETERANGAN gabungan
foreach ($merged as &$row) {
    // 1. Ambil hari & range lagi
    $dow   = Carbon::parse($row['tanggal'])->dayOfWeekIso;
    $range = ($dow >= 1 && $dow <= 4) ? $seninKamis : $jumat;

    $minMasuk  = Carbon::createFromFormat('H:i', $range['masuk_min']);
    $maxMasuk  = Carbon::createFromFormat('H:i', $range['masuk_max']);
    $minPulang = Carbon::createFromFormat('H:i', $range['pulang_min']);
    $maxPulang = Carbon::createFromFormat('H:i', $range['pulang_max']);

    // OVERRIDE: kalau **tidak ada jam masuk** → terlambat
    if (empty($row['jam_masuk'])) {
        $row['keterangan'] = 'terlambat';
        continue;
    }

    // OVERRIDE: kalau ada jam masuk tapi **tidak ada jam pulang** → terlambat
    if (! empty($row['jam_masuk']) && empty($row['jam_pulang'])) {
        $row['keterangan'] = 'terlambat';
        continue;
    }

    // 2. Hitung status masuk
    $statusMasuk = null;
    $jm = Carbon::createFromFormat('H:i', $row['jam_masuk']);
    if ($jm->lt($minMasuk)) {
        $statusMasuk = 'diluar waktu absen';
    } elseif ($jm->gt($maxMasuk)) {
        $statusMasuk = 'terlambat';
    } else {
        $statusMasuk = 'tepat waktu';
    }

    // 3. Hitung status pulang (jika ada)
    $statusPulang = null;
    if (! empty($row['jam_pulang'])) {
        $jp = Carbon::createFromFormat('H:i', $row['jam_pulang']);
        if ($jp->gt($maxPulang)) {
            $statusPulang = 'diluar waktu absen';
        } elseif ($jp->lt($minPulang)) {
            $statusPulang = 'terlambat';
        } else {
            $statusPulang = 'tepat waktu';
        }
    }

    // 4. Gabungkan prioritas
    $all = array_filter([$statusMasuk, $statusPulang]);
    if (in_array('diluar waktu absen', $all)) {
        $row['keterangan'] = 'diluar waktu absen';
    } elseif (in_array('terlambat', $all)) {
        $row['keterangan'] = 'terlambat';
    } else {
        $row['keterangan'] = 'tepat waktu';
    }
}
unset($row);

            $preview = array_values($merged);
            // --------------------------------------------------------------

            session(['preview_data' => $preview]);
        } else {
            $preview = session('preview_data', []);
        }

        if (count($preview) === 0) {
            return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
        }

        $collection = collect($preview);

        if ($search = $request->input('search')) {
            $collection = $collection->filter(fn($item) =>
                stripos($item['nama'], $search) !== false
            );
        }

        switch ($request->input('sort_by')) {
            case 'nama_asc':
                $collection = $collection->sortBy('nama');
                break;
            case 'nama_desc':
                $collection = $collection->sortByDesc('nama');
                break;
            case 'tanggal_asc':
                $collection = $collection->sortBy('tanggal');
                break;
            case 'tanggal_desc':
                $collection = $collection->sortByDesc('tanggal');
                break;
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage     = 40;
        $currentItems= $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedPreview = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

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
