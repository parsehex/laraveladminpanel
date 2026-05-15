<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Psr7\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('user.dashboard', compact('user'));
    }
    
}