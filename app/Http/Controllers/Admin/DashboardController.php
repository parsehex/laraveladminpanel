<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'admin_users' => User::where('role', 'admin')->count(),
        ];

        $recent_users = User::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recent_users'));
    }
}
