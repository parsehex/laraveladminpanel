<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DropdownController;
use App\Http\Controllers\Admin\ModelController;
use App\Http\Controllers\Admin\PartController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TruckApplianceController;
use App\Http\Controllers\Admin\TruckController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\User\AccountController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
| Example: granular route protection (OR semantics with "|")
| Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create|users.edit');
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('permission:admin.dashboard')
        ->name('dashboard');

    Route::get('dropdowns/categories', [DropdownController::class, 'categories'])
        ->middleware('permission:models.view|models.create|models.edit|appliance.create|appliance.edit')
        ->name('dropdowns.categories');
    Route::get('dropdowns/models', [DropdownController::class, 'models'])
        ->middleware('permission:parts.view|parts.create|parts.edit|models.view|appliance.create|appliance.edit')
        ->name('dropdowns.models');
    Route::post('dropdowns/categories', [DropdownController::class, 'storeCategory'])
        ->middleware('permission:category.create|models.create')
        ->name('dropdowns.categories.store');
    Route::post('dropdowns/models', [DropdownController::class, 'storeModel'])
        ->middleware('permission:models.create')
        ->name('dropdowns.models.store');

    Route::resource('users', UserController::class);

    Route::resource('roles', RoleController::class)->except(['show']);

    Route::get('parts', [PartController::class, 'index'])
        ->middleware('permission:parts.view')
        ->name('parts.index');
    Route::post('parts', [PartController::class, 'store'])
        ->middleware('permission:parts.create')
        ->name('parts.store');
    Route::put('parts/{part}', [PartController::class, 'update'])
        ->middleware('permission:parts.edit')
        ->name('parts.update');
    Route::delete('parts/{part}', [PartController::class, 'destroy'])
        ->middleware('permission:parts.delete')
        ->name('parts.destroy');

    Route::get('models', [ModelController::class, 'index'])
        ->middleware('permission:models.view')
        ->name('models.index');
    Route::post('models', [ModelController::class, 'store'])
        ->middleware('permission:models.create')
        ->name('models.store');
    Route::put('models/{model}', [ModelController::class, 'update'])
        ->middleware('permission:models.edit')
        ->name('models.update');
    Route::delete('models/{model}', [ModelController::class, 'destroy'])
        ->middleware('permission:models.delete')
        ->name('models.destroy');

    Route::post('trucks/{truck}/appliances', [TruckApplianceController::class, 'store'])
        ->middleware('permission:appliance.create')
        ->name('trucks.appliances.store');
    Route::put('trucks/{truck}/appliances/{appliance}', [TruckApplianceController::class, 'update'])
        ->middleware('permission:appliance.edit')
        ->name('trucks.appliances.update');
    Route::delete('trucks/{truck}/appliances/{appliance}', [TruckApplianceController::class, 'destroy'])
        ->middleware('permission:appliance.delete')
        ->name('trucks.appliances.destroy');

    Route::resource('trucks', TruckController::class);
});

Route::middleware(['auth', 'user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('/account/change-password/{uid}', [AccountController::class, 'changePassword'])
        ->name('account.changePassword');
    Route::put('/account/change-password/{uid}', [AccountController::class, 'updateChangePassword'])
        ->name('account.updatePassword');
    Route::resource('account', AccountController::class);
});
