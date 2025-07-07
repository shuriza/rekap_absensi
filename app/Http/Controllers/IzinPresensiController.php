<?php

// app/Http/Controllers/IzinPresensiController.php
namespace App\Http\Controllers;

use App\Models\IzinPresensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class IzinPresensiController extends Controller
{

     public function index()
    {
        // ambil semua data izin presensi, beserta relasi karyawan, urut terbaru
        $data = IzinPresensi::with('karyawan')
            ->orderBy('tanggal_awal', 'desc')
            ->paginate(10); // pakai pagination, 10 per halaman

        return view('izin_presensi.index', compact('data'));
    }
    public function create()
    {
        $listJenis = [
            'DL (DINAS LUAR) [TIDAK ADA PENGURANGAN]',
            'PDK (PENDIDIKAN) [TIDAK DAPAT TPP]',
            'SAKIT (1 HARI) [SURAT DOKTER]',
            'CB (CUTI BESAR) [4.5% / hari]',
            // dst...
        ];

        $tipeIjin = ['Ijin Penuh','Ijin Setengah','Terlambat','Pulang Cepat'];

        return view('izin_presensi.create', compact('listJenis','tipeIjin'));
    }

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

        if($request->hasFile('berkas')){
            $data['berkas'] = $request->file('berkas')->store('izin_berkas');
        }

        IzinPresensi::create($data);

        return redirect()
            ->route('izin_presensi.index')
            ->with('success','Izin presensi berhasil disimpan.');
    }

    // AJAX: search data karyawan untuk select2
    public function searchKaryawan(Request $request)
    {
        $q = $request->get('q','');
        $list = Karyawan::where('nama','like',"%{$q}%")
                  ->orWhere('nip','like',"%{$q}%")
                  ->limit(10)
                  ->get(['id','nip','nama']);

        return response()->json([
            'results' => $list->map(fn($k) => [
                'id'   => $k->id,
                'text' => "{$k->nama} — {$k->nip}",
                'nip'  => $k->nip,
                'nama' => $k->nama,
            ])
        ]);
    }
}
