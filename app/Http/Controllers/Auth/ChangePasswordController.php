<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ChangePasswordController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            // 'current_password' => ['required'],
            'new_password' => ['required', 'min:6', 'confirmed'],
        ]);

        /** @var User $user */
        $user = Auth::user(); // beri petunjuk ke IDE bahwa ini instance dari model User

        // if (!Hash::check($request->current_password, $user->password)) {
        //     return back()->withErrors(['current_password' => 'Password lama tidak sesuai.']);
        // }

        // Gunakan fill + save agar IDE tahu ini method dari Eloquent
        $user->fill([
            'password' => Hash::make($request->new_password),
        ])->save();

        return back()->with('status', 'Password berhasil diganti.');
    }
}
