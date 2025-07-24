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
    $bt     = $request->query('bulan_tahun');          // YYYY‑MM
    $sort   = $request->query('sort', 'tanggal_awal_desc');
    $q      = $request->query('q');

    /* Urai sort -> kolom & arah */
    [$kolom,$arah] = array_pad(explode('_',$sort),2,'desc');
    $arah  = $arah==='asc'?'asc':'desc';
    $valid = ['tanggal_awal','tanggal_akhir','tipe_ijin','nama'];
    $kolom = in_array($kolom,$valid) ? $kolom : 'tanggal_awal';

    $query = IzinPresensi::with('karyawan');

    if($bt){ [$y,$m] = explode('-',$bt); $query->whereYear('tanggal_awal',$y)->whereMonth('tanggal_awal',$m); }

    if($q){ $query->where(function($qr)use($q){ $qr->whereHas('karyawan',fn($k)=>$k->where('nama','like',"%$q%"))->orWhere('tipe_ijin','like',"%$q%" ); }); }

    $izinTbl = (new IzinPresensi)->getTable();
    if($kolom==='nama'){
        $query->join('karyawans','karyawans.id','=',$izinTbl.'.karyawan_id')
              ->orderBy('karyawans.nama',$arah)
              ->select($izinTbl.'.*');
    }else{
        $query->orderBy($kolom,$arah);
    }

    $data = $query->paginate(10)->withQueryString();
    return view('izin_presensi.index', compact('data','bt','sort','q'));
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