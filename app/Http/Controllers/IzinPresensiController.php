<?php

namespace App\Http\Controllers;

use App\Models\IzinPresensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class IzinPresensiController extends Controller
{
    /* ------------------------------------------------------------------
       1. LIST IZIN
    ------------------------------------------------------------------*/
    public function index(Request $request)
    {
        $bt   = $request->query('bulan_tahun');          // YYYY-MM
        $sort = $request->query('sort', 'nama_asc');
        $q    = $request->query('q');

        [$kolom, $arah] = array_pad(explode('_', $sort), 2, 'asc');
        $arah           = $arah === 'desc' ? 'desc' : 'asc';

        $validKolom = ['tanggal_awal', 'tanggal_akhir', 'tipe_ijin', 'nama'];
        $kolom      = in_array($kolom, $validKolom) ? $kolom : 'nama';

        $izinTbl = (new IzinPresensi)->getTable();
        $query   = IzinPresensi::with('karyawan');

        if ($bt) {
            [$y, $m] = explode('-', $bt);
            $query->whereYear('tanggal_awal', $y)->whereMonth('tanggal_awal', $m);
        }

        if ($q) {
            $query->where(function ($qr) use ($q) {
                $qr->whereHas('karyawan',
                        fn($k) => $k->where('nama', 'like', "%$q%"))
                   ->orWhere('tipe_ijin', 'like', "%$q%");
            });
        }

        if ($kolom === 'nama') {
            $query->join('karyawans', 'karyawans.id', '=', "$izinTbl.karyawan_id")
                  ->orderBy('karyawans.nama', $arah)
                  ->select("$izinTbl.*");
        } else {
            $query->orderBy($kolom, $arah);
        }

        $data = $query->paginate(10)->withQueryString();

        return view('izin_presensi.index', compact('data', 'bt', 'sort', 'q'));
    }

    /* ------------------------------------------------------------------
       2. FORM CREATE
    ------------------------------------------------------------------*/
    public function create()
    {
        $listJenis = [
            'DL (DINAS LUAR) [TIDAK ADA PENGURANGAN]',
            'PDK (PENDIDIKAN) [TIDAK DAPAT TPP]',
            'SAKIT (1 HARI) [SURAT DOKTER]',
            'CB (CUTI BESAR) [4.5% / hari]',
        ];

        $tipeIjin  = ['Ijin Penuh', 'Ijin Setengah', 'Terlambat', 'Pulang Cepat'];
        $karyawans = Karyawan::orderBy('nama')->get(['id', 'nama']);

        return view('izin_presensi.create', compact(
            'listJenis', 'tipeIjin', 'karyawans'
        ));
    }

    /* ------------------------------------------------------------------
       3. STORE  ➜  hapus izin lama yang overlap, lalu simpan baru
    ------------------------------------------------------------------*/
    public function store(Request $request)
    {
        /* 3-a. Validasi */
        $data = $request->validate([
            'karyawan_id'   => ['required', 'exists:karyawans,id'],
            'tipe_ijin'     => [
                                'required',
                                Rule::in(['Ijin Penuh','Ijin Setengah','Terlambat','Pulang Cepat'])
                              ],
            'tanggal_awal'  => ['required', 'date'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_awal'],
            'jenis_ijin'    => ['required', 'string'],
            'berkas'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'keterangan'    => ['nullable', 'string'],
        ]);

        /* 3-b. Normalisasi tanggal */
        $data['tanggal_akhir'] = $data['tanggal_akhir'] ?: $data['tanggal_awal'];

        /* 3-c. Upload file (jika ada) */
        if ($request->file('berkas')) {
            $data['berkas'] = $request->file('berkas')
                                      ->store('izin_presensi', 'public');
        }

        /* 3-d. Hapus izin lama yg rentangnya overlap */
        IzinPresensi::where('karyawan_id', $data['karyawan_id'])
            ->where(function ($q) use ($data) {
                $awal  = $data['tanggal_awal'];
                $akhir = $data['tanggal_akhir'];

                $q->whereBetween('tanggal_awal',  [$awal, $akhir])   // mulai di tengah
                  ->orWhereBetween('tanggal_akhir', [$awal, $akhir]) // berakhir di tengah
                  ->orWhere(function ($sub) use ($awal, $akhir) {     // menutup penuh
                        $sub->where('tanggal_awal', '<=', $awal)
                            ->where('tanggal_akhir','>=', $akhir);
                  });
            })->delete();

        /* 3-e. Simpan izin baru */
        IzinPresensi::create($data);

        /* 3-f. Redirect */
        return back()->with('success', 'Izin presensi berhasil diperbarui.');
    }

    /* ------------------------------------------------------------------
       4. SHOW
    ------------------------------------------------------------------*/
    public function show(IzinPresensi $izinPresensi)
    {
        return view('izin_presensi.show', compact('izinPresensi'));
    }

    /* ------------------------------------------------------------------
       5. DESTROY
    ------------------------------------------------------------------*/
    public function destroy(IzinPresensi $izinPresensi)
    {
        if ($izinPresensi->berkas) {
            Storage::disk('public')->delete($izinPresensi->berkas);
        }

        $izinPresensi->delete();

        return redirect()->route('izin_presensi.index')
                         ->with('success', 'Izin presensi berhasil dihapus.');
    }

    /* ------------------------------------------------------------------
       6. SELECT2  (AJAX search karyawan)
    ------------------------------------------------------------------*/
    public function searchKaryawan(Request $request)
    {
        $q = $request->get('q', '');

        $list = Karyawan::where('nama', 'like', "%{$q}%")
                        ->orderBy('nama')
                        ->limit(10)
                        ->get(['id', 'nama']);

        return response()->json([
            'results' => $list->map(fn ($k) => [
                'id'   => $k->id,
                'text' => $k->nama,
            ]),
        ]);
    }

    /* ------------------------------------------------------------------
       7. LAMPIRAN VIEWER
    ------------------------------------------------------------------*/
    public function lampiran(IzinPresensi $izin): Response
    {
        if (
            !$izin->berkas ||
            !Storage::disk('public')->exists($izin->berkas)
        ) {
            abort(404);
        }

        return Storage::disk('public')->response($izin->berkas);
    }
}
