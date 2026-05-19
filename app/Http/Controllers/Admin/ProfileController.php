<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($data);

        return back()->with('success', __('Profile updated successfully.'));
    }

    public function editPassword(Request $request)
    {
        return view('admin.profile.change-password', [
            'user' => $request->user(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => $data['password'],
        ]);

        return redirect()->route('admin.profile.edit')->with('success', __('Password updated successfully.'));
    }
}
