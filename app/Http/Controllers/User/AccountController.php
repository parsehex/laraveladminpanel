<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Log;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::findOrFail(decrypt($id));
        return view('user.actions.edit-profile', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $user = User::findOrFail(decrypt($id));
            $user->update($data);
            return redirect()->route('user.account.edit', ['account' => encrypt($user->id)])
                ->with('success', 'User updated successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('user.account.edit', ['account' => encrypt($user->id)])
                ->with('error', $th->getMessage());
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function changePassword(Request $request)
    {
        try {
            $user = User::findOrFail(decrypt($request->uid));
            return view('user.actions.change-password', compact('user'));
        } catch (\Throwable $th) {
            return redirect()->back()->with("error", $th->getMessage());
        }

        // dd($request->all());
    }
    public function updateChangePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();
        $user = User::findOrFail(decrypt($request->uid));

        $user->update($data);

        return redirect()->route('user.dashboard')
            ->with('success', 'User updated successfully.');
    }
}
