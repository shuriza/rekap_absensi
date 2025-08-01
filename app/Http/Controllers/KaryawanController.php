<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\NonaktifKaryawan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KaryawanController extends Controller
{
    public function index(Request $request)
    {
        $query = Karyawan::with('nonaktif_terbaru');

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%'.$request->search.'%');
        }

        $karyawans = $query->get();

        return view('absensi.karyawan', compact('karyawans'));
    }

    public function nonaktifkan(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);

        $request->validate([
            'tanggal_awal'  => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
        ]);

        NonaktifKaryawan::create([
            'karyawan_id'   => $karyawan->id,
            'tanggal_awal'  => $request->input('tanggal_awal'),
            'tanggal_akhir' => $request->input('tanggal_akhir'),
        ]);

        // Response untuk AJAX
        if ($request->wantsJson() || $request->ajax()) {
            $nonaktif = $karyawan->fresh()->nonaktif_terbaru;
            return response()->json([
                'tanggal_awal'  => Carbon::parse($nonaktif->tanggal_awal)->translatedFormat('d M Y'),
                'tanggal_akhir' => Carbon::parse($nonaktif->tanggal_akhir)->translatedFormat('d M Y'),
                'message' => 'Karyawan dinonaktifkan.',
            ]);
        }

        return redirect()->route('absensi.karyawan')->with('success', 'Karyawan dinonaktifkan.');
    }

    public function aktifkan(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);
        NonaktifKaryawan::where('karyawan_id', $karyawan->id)->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Karyawan diaktifkan kembali.']);
        }

        return redirect()->route('absensi.karyawan')->with('success', 'Karyawan diaktifkan kembali.');
    }
}
