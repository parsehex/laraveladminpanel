<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sanctum + permission middleware examples
|--------------------------------------------------------------------------
|
| Create a personal access token for a user (e.g. tinker):
| $user->createToken('api')->plainTextToken;
|
| Then call: Authorization: Bearer {token}
|
*/

Route::middleware(['auth:sanctum', 'permission:users.view'])->get('/v1/profile', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum', 'permission:users.view'])->get('/v1/users', function (Request $request) {
    return User::query()->select('id', 'name', 'email', 'status')->paginate(15);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
