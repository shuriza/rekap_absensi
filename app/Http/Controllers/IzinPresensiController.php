<?php

namespace App\Http\Controllers;

use App\Models\IzinPresensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class IzinPresensiController extends Controller
{

    /** Tampil list izin */
public function index(Request $request)
{
    /* ─────────────────────────────
     * 1. Ambil parameter filter
     * ────────────────────────────*/
    $bt        = $request->query('bulan_tahun');          // YYYY-MM (opsional)
    $start     = $request->query('start_date');
    $end       = $request->query('end_date');
    $sortParam = $request->query('sort', 'tanggal_awal_desc');
    $q         = $request->query('q');

    /* ── Pecah sort menjadi kolom & arah ──*/
    $parts  = explode('_', $sortParam);
    $sortBy = $parts[0] ?? 'tanggal_awal';
    $order  = $parts[1] ?? 'desc';               // default desc
    $order  = $order === 'asc' ? 'asc' : 'desc'; // sanitasi

    /* ── Validasi kolom yang diizinkan ──*/
    $validCols = ['tanggal_awal','tanggal_akhir','tipe_ijin','nama'];
    if (!in_array($sortBy, $validCols)) {
        $sortBy = 'tanggal_awal';
    }

    /* ─────────────────────────────
     * 2. Query dasar
     * ────────────────────────────*/
    $query = IzinPresensi::with('karyawan');

    // Filter bulan_tahun
    if ($bt) {
        [$tahun, $bulan] = explode('-', $bt);
        $query->whereYear('tanggal_awal',  $tahun)
              ->whereMonth('tanggal_awal', $bulan);
    }

    // Filter rentang tanggal
    if ($start && $end) {
        $query->whereBetween('tanggal_awal', [$start, $end]);
    }

    // Pencarian kata kunci
    if ($q) {
        $query->where(function ($qr) use ($q) {
            $qr->whereHas('karyawan', fn ($k) =>
                    $k->where('nama', 'like', "%{$q}%"))
               ->orWhere('tipe_ijin', 'like', "%{$q}%");
        });
    }

    /* ─────────────────────────────
     * 3. Sorting
     * ────────────────────────────*/
    $izinTable = (new IzinPresensi)->getTable();
    if ($sortBy === 'nama') {
        $query->join('karyawans', 'karyawans.id', '=', $izinTable.'.karyawan_id')
              ->orderBy('karyawans.nama', $order)
              ->select($izinTable.'.*');   // hindari kolom ganda
    } else {
        $query->orderBy($sortBy, $order);
    }

    /* ─────────────────────────────
     * 4. Paginasi & kirim ke view
     * ────────────────────────────*/
    $data = $query->paginate(10)->withQueryString();

    return view('izin_presensi.index', compact(
        'data', 'bt', 'start', 'end', 'sortParam', 'q'
    ));
}



    
    /** Form create */
    public function create()
    {
        $listJenis = [
            'DL (DINAS LUAR) [TIDAK ADA PENGURANGAN]',
            'PDK (PENDIDIKAN) [TIDAK DAPAT TPP]',
            'SAKIT (1 HARI) [SURAT DOKTER]',
            'CB (CUTI BESAR) [4.5% / hari]',
        ];
        $tipeIjin  = ['Ijin Penuh','Ijin Setengah','Terlambat','Pulang Cepat'];
        $karyawans = Karyawan::orderBy('nama')->get(['id','nama']);

        return view('izin_presensi.create', compact('listJenis','tipeIjin','karyawans'));
    }

    /** Simpan izin */
    public function store(Request $request)
    {
        $data = $request->validate([
            'karyawan_id'   => ['required','exists:karyawans,id'],
            'tipe_ijin'     => ['required', Rule::in(['Ijin Penuh','Ijin Setengah','Terlambat','Pulang Cepat'])],
            'tanggal_awal'  => ['required','date'],
            'tanggal_akhir' => ['nullable','date','after_or_equal:tanggal_awal'],
            'jenis_ijin'    => ['required','string'],
            'berkas'        => ['nullable','file','mimes:pdf,jpg,png','max:2048'],
            'keterangan'    => ['nullable','string'],
        ]);

        if ($request->file('berkas')) {
            // simpan di storage/app/public/izin_presensi
            $data['berkas'] = $request->file('berkas')->store('izin_presensi', 'public');
        }
            /* jika tanggal_akhir kosong, set = tanggal_awal */
            $data['tanggal_akhir'] = $data['tanggal_akhir'] ?: $data['tanggal_awal'];

            /* 2️⃣  Hapus izin lama yg rentangnya bentrok */
            IzinPresensi::where('karyawan_id', $data['karyawan_id'])
                ->where(function($q) use ($data) {
                    // Tiga kemungkinan overlap
                    $q->whereBetween('tanggal_awal',  [$data['tanggal_awal'], $data['tanggal_akhir']])
                    ->orWhereBetween('tanggal_akhir',[$data['tanggal_awal'], $data['tanggal_akhir']])
                    ->orWhere(function($x) use ($data){
                            $x->where('tanggal_awal',  '<=', $data['tanggal_awal'])
                            ->where('tanggal_akhir', '>=', $data['tanggal_akhir']);
                    });
                })->delete();

        IzinPresensi::create($data);

        return redirect()->route('izin_presensi.index')
            ->with('success', 'Izin presensi berhasil disimpan.');
    }

    /** Detail */
    public function show(IzinPresensi $izinPresensi)
    {
        return view('izin_presensi.show', compact('izinPresensi'));
    }

    /** Hapus */
    public function destroy(IzinPresensi $izinPresensi)
    {
        if ($izinPresensi->berkas) {
            Storage::disk('public')->delete($izinPresensi->berkas);
        }

        $izinPresensi->delete();

        return redirect()->route('izin_presensi.index')
            ->with('success', 'Izin presensi berhasil dihapus.');
    }

    /** AJAX dropdown karyawan */
    public function searchKaryawan(Request $request)
    {
        $q = $request->get('q', '');

        $list = Karyawan::where('nama', 'like', "%{$q}%")
                        ->orderBy('nama')
                        ->limit(10)
                        ->get(['id', 'nama']);

        return response()->json([
            'results' => $list->map(fn($k) => [
                'id'   => $k->id,
                'text' => $k->nama,   // hanya nama
            ]),
        ]);
    }

    

    public function lampiran(IzinPresensi $izin): Response
    {
        // boleh tambahkan pengecekan role/user di sini
        if (!$izin->berkas || !Storage::disk('public')->exists($izin->berkas)) {
            abort(404);
        }

        // tampilkan langsung di browser
        return Storage::disk('public')->response($izin->berkas);
        // atau ->download($izin->berkas) bila ingin force-download
    }


}