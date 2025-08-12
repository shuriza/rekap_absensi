<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Absensi;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AbsensiController extends Controller
{
    public function index()
    {
        return view('absensi.index');
    }

    /** Helper: normalisasi nama/departemen (trim + collapse space + upper)
     *  dipakai agar pencarian karyawan konsisten
     */
    private function norm(string $v): string
    {
        $v = preg_replace('/\s+/u', ' ', trim($v ?? ''));
        return mb_strtoupper($v, 'UTF-8');
    }

    /** Helper: normalisasi jam ke HH:MM */
    private function normTime(?string $t): ?string
    {
        if (!$t) return null;
        [$h, $i] = array_pad(explode(':', $t), 2, '00');
        return sprintf('%02d:%02d', (int)$h, (int)$i);
    }

    /** Helper: pilih status dengan prioritas tertinggi */
    private function pickStatus(string $a = null, string $b = null): ?string
    {
        $prio = ['tepat waktu' => 1, 'terlambat' => 2, 'diluar waktu absen' => 3, 'tidak valid' => 0];
        $ka = $a ? ($prio[$a] ?? 0) : -1;
        $kb = $b ? ($prio[$b] ?? 0) : -1;

        if ($ka === -1) return $b;
        if ($kb === -1) return $a;
        return $ka >= $kb ? $a : $b;
    }

    public function preview(Request $request)
    {
        // Validasi input untuk POST request
        if ($request->isMethod('post')) {
            $this->validatePreviewRequest($request);
        }

        // Konfigurasi rentang jam kerja
        $timeRanges = $this->buildTimeRanges($request);

        // Proses file Excel dan parse data
        $preview = [];
        if ($request->hasFile('file_excel')) {
            $preview = $this->processExcelFiles($request, $timeRanges);
            
            if (is_a($preview, 'Illuminate\Http\RedirectResponse')) {
                return $preview; // Return error response
            }
        } else {
            $preview = session('preview_data_all', []);
        }

        // Validasi data kosong
        if (empty($preview)) {
            return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
        }

        // Filter dan sorting data
        $filteredData = $this->filterAndSortData($preview, $request);

        // Paginasi data untuk tampilan
        $paginatedPreview = $this->paginateData($filteredData, $request);

        return view('absensi.index', [
            'preview' => $paginatedPreview,
        ]);
    }

    /**
     * Validasi request untuk preview data
     */
    private function validatePreviewRequest(Request $request): void
    {
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

            // optional Ramadhan
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

    /**
     * Bangun konfigurasi rentang jam kerja
     */
    private function buildTimeRanges(Request $request): array
    {
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

        $ramadhanRange = $this->buildRamadhanRange($request);

        return [
            'senin_kamis' => $seninKamis,
            'jumat' => $jumat,
            'ramadhan' => $ramadhanRange,
        ];
    }

    /**
     * Bangun konfigurasi rentang jam Ramadhan
     */
    private function buildRamadhanRange(Request $request): ?array
    {
        $ramadhanStartDate = $request->input('ramadhan_start_date');
        $ramadhanEndDate = $request->input('ramadhan_end_date');

        if (!$ramadhanStartDate || !$ramadhanEndDate) {
            return null;
        }

        return [
            'start_date' => Carbon::parse($ramadhanStartDate)->startOfDay(),
            'end_date'   => Carbon::parse($ramadhanEndDate)->endOfDay(),
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
    }

    /**
     * Proses file Excel dan ekstrak data absensi
     */
    private function processExcelFiles(Request $request, array $timeRanges)
    {
        $preview = [];
        $bulanTahunSet = [];

        foreach ($request->file('file_excel') as $file) {
            $result = $this->processExcelFile($file, $timeRanges, $preview, $bulanTahunSet);
            
            if (is_a($result, 'Illuminate\Http\RedirectResponse')) {
                return $result; // Return error response
            }
        }

        // Validasi konsistensi bulan
        $bulanUnik = array_unique($bulanTahunSet);
        if (count($bulanUnik) > 1) {
            return back()->with('error', 'Bulan tidak sama antara file!');
        }

        // Simpan ke session
        $preview = array_values($preview);
        session(['preview_data_all' => $preview]);

        return $preview;
    }

    /**
     * Proses satu file Excel
     */
    private function processExcelFile($file, array $timeRanges, array &$preview, array &$bulanTahunSet)
    {
        $data = Excel::toArray(new \stdClass(), $file->getRealPath());
        $sheet = $data[2] ?? [];         // sheet ke-3
        $barisTanggal = $sheet[3] ?? []; // row ke-4 sebagai header tanggal

        // Parse tanggal dari cell C3
        $dateRange = $this->parseDateRangeFromCell($sheet);
        if (!$dateRange) {
            return back()->with('error', 'Cell C3 tidak ditemukan atau tidak valid.');
        }

        $bulanTahunSet[] = sprintf('%04d-%02d', $dateRange['start']->year, $dateRange['start']->month);

        // Proses data karyawan
        for ($i = 4; $i < count($sheet); $i += 2) {
            $this->processEmployeeData($sheet, $i, $barisTanggal, $dateRange, $timeRanges, $preview);
        }

        return true;
    }

    /**
     * Parse rentang tanggal dari cell C3
     */
    private function parseDateRangeFromCell(array $sheet): ?array
    {
        $cellC3 = $sheet[2][2] ?? null;
        if (!$cellC3) {
            return null;
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

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Proses data absensi satu karyawan
     */
    private function processEmployeeData(array $sheet, int $rowIndex, array $barisTanggal, array $dateRange, array $timeRanges, array &$preview): void
    {
        $infoRow = $sheet[$rowIndex];
        $dataRow = $sheet[$rowIndex + 1] ?? [];

        $nama = $infoRow[10] ?? null;
        $departemen = $infoRow[20] ?? null;
        
        if (!$nama || !$departemen) {
            return;
        }

        $normNama = $this->norm($nama);
        $normDept = $this->norm($departemen);

        // Proses data harian (kolom 1-31)
        for ($col = 1; $col <= 31; $col++) {
            $this->processAttendanceData(
                $barisTanggal, 
                $dataRow, 
                $col, 
                $dateRange, 
                $timeRanges, 
                $normNama, 
                $normDept, 
                $preview
            );
        }
    }

    /**
     * Proses data absensi harian
     */
    private function processAttendanceData(array $barisTanggal, array $dataRow, int $col, array $dateRange, array $timeRanges, string $normNama, string $normDept, array &$preview): void
    {
        $tanggalKe = $barisTanggal[$col] ?? null;
        $raw = $dataRow[$col] ?? null;
        
        if (!$tanggalKe || !$raw) {
            return;
        }

        // Parse jam dari data Excel
        $jamList = $this->parseTimeFromExcel($raw);
        if (empty($jamList)) {
            return;
        }

        // Buat tanggal lengkap dan validasi range
        $tgl = Carbon::createFromDate($dateRange['start']->year, $dateRange['start']->month, (int)$tanggalKe)->format('Y-m-d');
        if (Carbon::parse($tgl)->lt($dateRange['start']) || Carbon::parse($tgl)->gt($dateRange['end'])) {
            return;
        }

        // Tentukan range jam berdasarkan hari dan periode Ramadhan
        $range = $this->determineTimeRange(Carbon::parse($tgl), $timeRanges);
        if (!$range) {
            return; // Skip weekend
        }

        // Tentukan jam masuk dan pulang yang valid
        $validTimes = $this->determineValidTimes($jamList, $range);
        if (!$validTimes['jam_masuk'] && !$validTimes['jam_pulang']) {
            return;
        }

        // Generate keterangan status
        $keterangan = $this->generateAttendanceStatus($validTimes, $range);

        // Merge atau simpan data
        $this->mergeOrStoreAttendanceData($preview, $normNama, $normDept, $tgl, $validTimes, $keterangan);
    }

    /**
     * Parse waktu dari data Excel
     */
    private function parseTimeFromExcel($raw): array
    {
        $jamList = [];
        
        if (is_numeric($raw)) {
            try {
                $dt2 = Date::excelToDateTimeObject($raw);
                $jamList[] = Carbon::instance($dt2)->format('H:i');
            } catch (\Exception $e) {
                // Skip jika error parsing
            }
        } elseif (is_string($raw)) {
            preg_match_all('/\d{1,2}:\d{2}/', $raw, $m);
            $jamList = array_map(function ($t) {
                [$h, $i] = explode(':', $t);
                return sprintf('%02d:%02d', (int)$h, (int)$i);
            }, $m[0] ?? []);
        }

        return $jamList;
    }

    /**
     * Tentukan range jam berdasarkan hari dan periode
     */
    private function determineTimeRange(Carbon $tanggal, array $timeRanges): ?array
    {
        $ramadhanRange = $timeRanges['ramadhan'];
        
        // Cek apakah dalam periode Ramadhan
        if ($ramadhanRange && $tanggal->between($ramadhanRange['start_date'], $ramadhanRange['end_date'])) {
            return ($tanggal->dayOfWeekIso === 5) ? $ramadhanRange['jumat'] : $ramadhanRange['senin_kamis'];
        }

        // Hari biasa
        $dow = $tanggal->dayOfWeekIso;
        if ($dow >= 1 && $dow <= 4) { // Senin-Kamis
            return $timeRanges['senin_kamis'];
        } elseif ($dow === 5) { // Jumat
            return $timeRanges['jumat'];
        }

        return null; // Weekend
    }

    /**
     * Tentukan jam masuk dan pulang yang valid
     */
    private function determineValidTimes(array $jamList, array $range): array
    {
        sort($jamList);
        
        $masukMin = Carbon::createFromFormat('H:i', $range['masuk_min']);
        $masukMax = Carbon::createFromFormat('H:i', $range['masuk_max']);
        $pulangMin = Carbon::createFromFormat('H:i', $range['pulang_min']);
        $pulangMax = Carbon::createFromFormat('H:i', $range['pulang_max']);

        $jamMasukValid = null;
        $jamPulangValid = null;

        // Cari jam masuk valid
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

        // Cari jam pulang valid
        foreach (array_reverse($jamList) as $j) {
            $jObj = Carbon::createFromFormat('H:i', $j);
            if ($jamMasukValid && $j > $jamMasukValid && $jObj->betweenIncluded($pulangMin, $pulangMax)) {
                $jamPulangValid = $j;
                break;
            }
        }
        if (!$jamPulangValid && count($jamList) > 1 && end($jamList) > $jamMasukValid) {
            $jamPulangValid = end($jamList);
        }

        return [
            'jam_masuk' => $jamMasukValid,
            'jam_pulang' => $jamPulangValid,
        ];
    }

    /**
     * Generate status keterangan absensi
     */
    private function generateAttendanceStatus(array $validTimes, array $range): ?string
    {
        $jm = $validTimes['jam_masuk'] ? Carbon::createFromFormat('H:i', $validTimes['jam_masuk']) : null;
        $jp = $validTimes['jam_pulang'] ? Carbon::createFromFormat('H:i', $validTimes['jam_pulang']) : null;

        if ($jm && !$jp) {
            return 'tidak valid';
        } elseif (!$jm && $jp) {
            return 'tidak valid';
        } elseif ($jm && $jp) {
            $masukMin = Carbon::createFromFormat('H:i', $range['masuk_min']);
            $masukMax = Carbon::createFromFormat('H:i', $range['masuk_max']);
            $pulangMin = Carbon::createFromFormat('H:i', $range['pulang_min']);
            $pulangMax = Carbon::createFromFormat('H:i', $range['pulang_max']);

            $sMasuk = $jm->lt($masukMin) ? 'diluar waktu absen' : ($jm->gt($masukMax) ? 'terlambat' : 'tepat waktu');
            $sPulang = $jp->gt($pulangMax) ? 'diluar waktu absen' : ($jp->lt($pulangMin) ? 'terlambat' : 'tepat waktu');
            
            return $this->pickStatus($sMasuk, $sPulang);
        }

        return null;
    }

    /**
     * Merge atau simpan data absensi
     */
    private function mergeOrStoreAttendanceData(array &$preview, string $normNama, string $normDept, string $tgl, array $validTimes, ?string $keterangan): void
    {
        $key = $normNama . '|' . $normDept . '|' . $tgl;

        $row = [
            'nama' => $normNama,
            'departemen' => $normDept,
            'tanggal' => $tgl,
            'jam_masuk' => $this->normTime($validTimes['jam_masuk']),
            'jam_pulang' => $this->normTime($validTimes['jam_pulang']),
            'keterangan' => $keterangan,
        ];

        if (!isset($preview[$key])) {
            $preview[$key] = $row;
        } else {
            // Merge data: ambil jam masuk paling awal dan jam pulang paling akhir
            $existing = $preview[$key];

            $jmNew = $row['jam_masuk'];
            $jmOld = $existing['jam_masuk'];
            $pickMasuk = (!$jmOld || ($jmNew && $jmNew < $jmOld)) ? $jmNew : $jmOld;

            $jpNew = $row['jam_pulang'];
            $jpOld = $existing['jam_pulang'];
            $pickPulang = (!$jpOld || ($jpNew && $jpNew > $jpOld)) ? $jpNew : $jpOld;

            $preview[$key] = [
                'nama' => $existing['nama'],
                'departemen' => $existing['departemen'],
                'tanggal' => $existing['tanggal'],
                'jam_masuk' => $pickMasuk,
                'jam_pulang' => $pickPulang,
                'keterangan' => $this->pickStatus($existing['keterangan'], $row['keterangan']),
            ];
        }
    }

    /**
     * Filter dan sorting data
     */
    private function filterAndSortData(array $preview, Request $request)
    {
        $collection = collect($preview);

        // Filter pencarian
        if ($search = $request->input('search')) {
            $needle = mb_strtoupper(trim($search), 'UTF-8');
            $collection = $collection->filter(fn($row) => 
                Str::contains($row['nama'], $needle)
            );
        }

        // Sorting
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

        // Simpan hasil filter ke session
        session(['preview_data' => $collection->values()->all()]);

        return $collection;
    }

    /**
     * Paginasi data untuk tampilan
     */
    private function paginateData($collection, Request $request): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    public function store(Request $request)
    {
        $data = session('preview_data');

        if (!$this->validateStoreData($data)) {
            return back()->with('error', 'Tidak ada data yang bisa disimpan.');
        }

        if (!$this->validateSingleMonth($data)) {
            return back()->with('error', 'Data yang akan disimpan mengandung lebih dari satu bulan.');
        }

        $this->deleteExistingData($data);
        $this->saveAttendanceData($data);
        $this->clearSessionData();

        return redirect()->route('absensi.index')->with('success', 'Semua data absensi berhasil disimpan!');
    }

    /**
     * Validasi data yang akan disimpan
     */
    private function validateStoreData($data): bool
    {
        return is_array($data) && !empty($data);
    }

    /**
     * Validasi bahwa semua data dalam satu bulan yang sama
     */
    private function validateSingleMonth(array $data): bool
    {
        $bulanTahun = collect($data)
            ->map(fn($r) => Carbon::parse($r['tanggal'])->format('Y-m'))
            ->unique()
            ->values();

        return $bulanTahun->count() === 1;
    }

    /**
     * Hapus data absensi yang sudah ada untuk bulan yang sama
     */
    private function deleteExistingData(array $data): void
    {
        $firstDate = Carbon::parse($data[0]['tanggal']);
        $year = $firstDate->year;
        $month = $firstDate->month;

        Absensi::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->delete();
    }

    /**
     * Simpan data absensi ke database
     */
    private function saveAttendanceData(array $data): void
    {
        foreach ($data as $row) {
            $karyawan = $this->findOrCreateEmployee($row['nama'], $row['departemen']);
            $this->saveEmployeeAttendance($karyawan, $row);
        }
    }

    /**
     * Cari atau buat karyawan baru
     */
    private function findOrCreateEmployee(string $nama, string $departemen): Karyawan
    {
        $namaNorm = $this->norm($nama);
        $deptNorm = $this->norm($departemen);

        $karyawan = Karyawan::whereRaw('UPPER(TRIM(nama)) = ?', [$namaNorm])
            ->whereRaw('UPPER(TRIM(departemen)) = ?', [$deptNorm])
            ->first();

        if (!$karyawan) {
            $karyawan = Karyawan::create([
                'nama' => $namaNorm,
                'departemen' => $deptNorm,
            ]);
        }

        return $karyawan;
    }

    /**
     * Simpan data absensi karyawan
     */
    private function saveEmployeeAttendance(Karyawan $karyawan, array $row): void
    {
        Absensi::updateOrCreate(
            [
                'karyawan_id' => $karyawan->id,
                'tanggal' => $row['tanggal'],
            ],
            [
                'jam_masuk' => $this->normTime($row['jam_masuk']),
                'jam_pulang' => $this->normTime($row['jam_pulang']),
                'keterangan' => $row['keterangan'] ?? null,
            ]
        );
    }

    /**
     * Bersihkan data session
     */
    private function clearSessionData(): void
    {
        session()->forget(['preview_data', 'preview_data_all']);
    }
}
