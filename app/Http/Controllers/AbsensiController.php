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
            'file_excel.*'            => 'required|mimes:xlsx,xls',
            'jam_masuk_min_senin'     => 'required|date_format:H:i',
            'jam_masuk_max_senin'     => 'required|date_format:H:i',
            'jam_pulang_min_senin'    => 'required|date_format:H:i',
            'jam_pulang_max_senin'    => 'required|date_format:H:i',
            'jam_masuk_min_jumat'     => 'required|date_format:H:i',
            'jam_masuk_max_jumat'     => 'required|date_format:H:i',
            'jam_pulang_min_jumat'    => 'required|date_format:H:i',
            'jam_pulang_max_jumat'    => 'required|date_format:H:i',
        ]);
    }

    // 2. Siapkan container hasil dan catat bulan-tahun
    $preview       = [];
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
            $data         = Excel::toArray([], $file->getRealPath());
            $sheet        = $data[2] ?? [];        // sheet ke-3
            $barisTanggal = $sheet[3] ?? [];       // row ke-4 sebagai header tanggal

            // 4a. Parse rentang tanggal dari C3
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

            // 4b. Loop baris data (dua-dua: infoRow & dataRow)
            for ($i = 4; $i < count($sheet); $i += 2) {
                $infoRow = $sheet[$i];
                $dataRow = $sheet[$i + 1] ?? [];

                $nama       = $infoRow[10] ?? null;
                $departemen = $infoRow[20] ?? null;
                if (! $nama || ! $departemen) continue;

                // 4c. Untuk setiap kolom tanggal 1–30
                for ($col = 1; $col <= 30; $col++) {
                    $tanggalKe = $barisTanggal[$col] ?? null;
                    $raw       = $dataRow[$col]      ?? null;
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

                    // 4d. Tambahkan semua jam ke $preview
                    foreach ($jamList as $j) {
                        $j      = trim($j);
                        $jamObj = Carbon::createFromFormat('H:i', $j);
                        $pushed = false;

                        // dalam window masuk?
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

                        // dalam window pulang?
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

                        // di luar kedua window → asumsikan pagi/sore
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

        // 5. Validasi bulan sama & hapus data lama
        $bulanUnik = array_unique($bulanTahunSet);
        if (count($bulanUnik) > 1) {
            return back()->with('error', 'Bulan tidak sama antara file!');
        }
        [$y, $m] = explode('-', $bulanUnik[0]);
        Absensi::whereYear('tanggal', $y)
               ->whereMonth('tanggal', $m)
               ->delete();

        // 6. MERGE jam_masuk & jam_pulang per (nama,departemen,tanggal)
        $merged = [];
        foreach ($preview as $row) {
            $key = "{$row['nama']}|{$row['departemen']}|{$row['tanggal']}";
            if (! isset($merged[$key])) {
                $merged[$key] = [
                    'nama'       => $row['nama'],
                    'departemen' => $row['departemen'],
                    'tanggal'    => $row['tanggal'],
                    'jam_masuk'  => null,
                    'jam_pulang' => null,
                ];
            }
            if ($row['jam_masuk']) {
                $merged[$key]['jam_masuk'] = $row['jam_masuk'];
            }
            if ($row['jam_pulang']) {
                $merged[$key]['jam_pulang'] = $row['jam_pulang'];
            }
        }

        // 7. Hitung satu kolom keterangan dengan override
        foreach ($merged as &$row) {
            $dow   = Carbon::parse($row['tanggal'])->dayOfWeekIso;
            $range= ($dow >= 1 && $dow <= 4) ? $seninKamis : $jumat;
            $minM = Carbon::createFromFormat('H:i', $range['masuk_min']);
            $maxM = Carbon::createFromFormat('H:i', $range['masuk_max']);
            $minP = Carbon::createFromFormat('H:i', $range['pulang_min']);
            $maxP = Carbon::createFromFormat('H:i', $range['pulang_max']);

            // override: tanpa jam_masuk → terlambat
            if (empty($row['jam_masuk'])) {
                $row['keterangan'] = 'terlambat';
                continue;
            }
            // override: tanpa jam_pulang → terlambat
            if ($row['jam_masuk'] && empty($row['jam_pulang'])) {
                $row['keterangan'] = 'terlambat';
                continue;
            }

            // status masuk
            $jm = Carbon::createFromFormat('H:i', $row['jam_masuk']);
            if ($jm->lt($minM))        $sMasuk = 'diluar waktu absen';
            elseif ($jm->gt($maxM))    $sMasuk = 'terlambat';
            else                        $sMasuk = 'tepat waktu';

            // status pulang (jika ada)
            if ($row['jam_pulang']) {
                $jp = Carbon::createFromFormat('H:i', $row['jam_pulang']);
                if ($jp->gt($maxP))     $sPulang = 'diluar waktu absen';
                elseif ($jp->lt($minP)) $sPulang = 'terlambat';
                else                    $sPulang = 'tepat waktu';
            } else {
                $sPulang = null;
            }

            // gabungkan prioritas
            $all = array_filter([$sMasuk, $sPulang]);
            if (in_array('diluar waktu absen', $all))      $row['keterangan'] = 'diluar waktu absen';
            elseif (in_array('terlambat', $all))           $row['keterangan'] = 'terlambat';
            else                                           $row['keterangan'] = 'tepat waktu';
        }
        unset($row);

        // 8. Simpan preview ke session
        $preview = array_values($merged);
        session(['preview_data' => $preview]);
    } else {
        // GET: ambil dari session
        $preview = session('preview_data', []);
    }

    // 9. Jika kosong
    if (count($preview) === 0) {
        return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
    }

    // 10. Filter & Sort
    $collection = collect($preview);
    if ($search = $request->input('search')) {
        $collection = $collection->filter(fn($row) =>
            stripos($row['nama'], $search) !== false
        );
    }
    switch ($request->input('sort_by')) {
        case 'nama_asc':    $collection = $collection->sortBy('nama');       break;
        case 'nama_desc':   $collection = $collection->sortByDesc('nama');   break;
        case 'tanggal_asc': $collection = $collection->sortBy('tanggal');    break;
        case 'tanggal_desc':$collection = $collection->sortByDesc('tanggal');break;
    }

    // 11. Paginasi
    $currentPage  = LengthAwarePaginator::resolveCurrentPage();
    $perPage      = 25;
    $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
    $paginatedPreview = new LengthAwarePaginator(
        $currentItems,
        $collection->count(),
        $perPage,
        $currentPage,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    // 12. Kembalikan view
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
