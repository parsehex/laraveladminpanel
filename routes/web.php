<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\DropdownController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\KitController;
use App\Http\Controllers\Admin\ModelController;
use App\Http\Controllers\Admin\PartController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\TruckApplianceController;
use App\Http\Controllers\Admin\TruckController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
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
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::get('/profile/change-password', [ProfileController::class, 'editPassword'])
        ->name('profile.password.edit');
    Route::put('/profile/change-password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');
    Route::post('/dashboard/suggestions', [AdminDashboardController::class, 'storeSuggestion'])
        ->middleware('permission:admin.dashboard')
        ->name('dashboard.suggestions.store');
    Route::post('/dashboard/suggestions/{suggestion}/responses', [AdminDashboardController::class, 'storeSuggestionResponse'])
        ->middleware('permission:admin.dashboard')
        ->name('dashboard.suggestions.responses.store');
    Route::patch('/dashboard/suggestions/{suggestion}/complete', [AdminDashboardController::class, 'completeSuggestion'])
        ->middleware('permission:admin.dashboard')
        ->name('dashboard.suggestions.complete');

    Route::get('dropdowns/categories', [DropdownController::class, 'categories'])
        ->middleware('permission:models.view|models.create|models.edit|appliance.create|appliance.edit')
        ->name('dropdowns.categories');
    Route::get('dropdowns/models', [DropdownController::class, 'models'])
        ->middleware('permission:parts.view|parts.create|parts.edit|models.view|appliance.create|appliance.edit')
        ->name('dropdowns.models');
    Route::get('dropdowns/brands', [DropdownController::class, 'brands'])
        ->middleware('permission:models.view|models.create|models.edit|appliance.create|appliance.edit')
        ->name('dropdowns.brands');
    Route::get('dropdowns/kit-parts', [DropdownController::class, 'kitParts'])
        ->middleware('permission:kits.view|kits.manage')
        ->name('dropdowns.kit-parts');
    Route::post('dropdowns/categories', [DropdownController::class, 'storeCategory'])
        ->middleware('permission:category.create')
        ->name('dropdowns.categories.store');
    Route::post('dropdowns/models', [DropdownController::class, 'storeModel'])
        ->middleware('permission:models.create')
        ->name('dropdowns.models.store');
    Route::post('dropdowns/brands', [DropdownController::class, 'storeBrand'])
        ->middleware('permission:models.create|models.edit|appliance.create|appliance.edit')
        ->name('dropdowns.brands.store');
    Route::post('dropdowns/kit-parts', [DropdownController::class, 'storeKitPart'])
        ->middleware('permission:kits.manage')
        ->name('dropdowns.kit-parts.store');

    Route::resource('users', UserController::class);

    Route::resource('roles', RoleController::class)->except(['show']);

    Route::get('sales', [SalesController::class, 'index'])
        ->middleware('permission:sales.view')
        ->name('sales.index');
    Route::post('sales/mark-sold', [SalesController::class, 'markSold'])
        ->middleware('permission:sales.create')
        ->name('sales.mark-sold');
    Route::patch('sales/{appliance}/sold-price', [SalesController::class, 'updateSoldPrice'])
        ->middleware('permission:sales.edit')
        ->name('sales.sold-price.update');

    Route::get('deliveries', [DeliveryController::class, 'index'])
        ->middleware('permission:deliveries.view')
        ->name('deliveries.index');
    Route::post('deliveries', [DeliveryController::class, 'store'])
        ->middleware('permission:deliveries.create')
        ->name('deliveries.store');
    Route::delete('deliveries/{delivery}', [DeliveryController::class, 'destroy'])
        ->middleware('permission:deliveries.delete')
        ->name('deliveries.destroy');

    Route::get('kits', [KitController::class, 'index'])
        ->middleware('permission:kits.view')
        ->name('kits.index');
    Route::post('kits', [KitController::class, 'store'])
        ->middleware('permission:kits.manage')
        ->name('kits.store');
    Route::delete('kits/{kit}', [KitController::class, 'destroy'])
        ->middleware('permission:kits.manage')
        ->name('kits.destroy');
    Route::post('kits/{kit}/parts', [KitController::class, 'addParts'])
        ->middleware('permission:kits.manage')
        ->name('kits.parts.store');
    Route::delete('kits/{kit}/parts/{part}', [KitController::class, 'destroyPart'])
        ->middleware('permission:kits.manage')
        ->name('kits.parts.destroy');
    Route::post('kits/assignments', [KitController::class, 'assign'])
        ->middleware('permission:kits.manage')
        ->name('kits.assignments.store');
    Route::patch('kits/assignments/{assignment}/start', [KitController::class, 'start'])
        ->middleware('permission:kits.build|kits.manage')
        ->name('kits.assignments.start');
    Route::patch('kits/assignments/{assignment}/built', [KitController::class, 'built'])
        ->middleware('permission:kits.build|kits.manage')
        ->name('kits.assignments.built');
    Route::patch('kits/assignments/{assignment}/confirm', [KitController::class, 'confirm'])
        ->middleware('permission:kits.manage')
        ->name('kits.assignments.confirm');
    Route::delete('kits/assignments/{assignment}', [KitController::class, 'destroyAssignment'])
        ->middleware('permission:kits.manage')
        ->name('kits.assignments.destroy');
    Route::post('kits/assignments/{assignment}/messages', [KitController::class, 'message'])
        ->middleware('permission:kits.build|kits.manage')
        ->name('kits.messages.store');
    Route::post('kits/inventory/adjust-stock', [KitController::class, 'adjustStock'])
        ->middleware('permission:kits.manage')
        ->name('kits.inventory.adjust-stock');
    Route::post('kits/inventory/adjust-min-level', [KitController::class, 'adjustMinLevel'])
        ->middleware('permission:kits.manage')
        ->name('kits.inventory.adjust-min-level');
    Route::post('kits/resources', [KitController::class, 'storeResource'])
        ->middleware('permission:kits.manage')
        ->name('kits.resources.store');
    Route::delete('kits/resources/{resource}', [KitController::class, 'destroyResource'])
        ->middleware('permission:kits.manage')
        ->name('kits.resources.destroy');
    Route::get('kits/{kit}/sop', [KitController::class, 'sop'])
        ->middleware('permission:kits.view')
        ->name('kits.sop');

    Route::get('inventory', [InventoryController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('inventory.index');
    Route::get('inventory/parts/search', [InventoryController::class, 'searchParts'])
        ->middleware('permission:parts.view|appliance.edit')
        ->name('inventory.parts.search');
    Route::get('inventory/stickers', [InventoryController::class, 'stickers'])
        ->middleware('permission:inventory.view')
        ->name('inventory.stickers');
    Route::get('inventory/{appliance}', [InventoryController::class, 'show'])
        ->middleware('permission:inventory.view')
        ->name('inventory.show');
    Route::patch('inventory/{appliance}/location', [InventoryController::class, 'updateLocation'])
        ->middleware('permission:appliance.edit')
        ->name('inventory.location.update');
    Route::patch('inventory/{appliance}/move-truck', [InventoryController::class, 'moveTruck'])
        ->middleware('permission:appliance.edit')
        ->name('inventory.move-truck.update');
    Route::patch('inventory/{appliance}/status', [InventoryController::class, 'updateStatus'])
        ->middleware('permission:appliance.edit')
        ->name('inventory.status.update');
    Route::post('inventory/{appliance}/parts', [InventoryController::class, 'storePart'])
        ->middleware('permission:appliance.edit')
        ->name('inventory.parts.store');
    Route::delete('inventory/{appliance}/parts/{part}', [InventoryController::class, 'destroyPart'])
        ->middleware('permission:appliance.edit')
        ->name('inventory.parts.destroy');
    Route::post('inventory/{appliance}/photos', [InventoryController::class, 'uploadPhotos'])
        ->middleware('permission:appliance.edit')
        ->name('inventory.photos.store');
    Route::get('inventory/{appliance}/photos', [InventoryController::class, 'photos'])
        ->middleware('permission:inventory.view')
        ->name('inventory.photos.index');
    Route::get('inventory/{appliance}/photos/view', [InventoryController::class, 'showPhoto'])
        ->middleware('permission:inventory.view')
        ->name('inventory.photos.show');
    Route::delete('inventory/{appliance}/photos', [InventoryController::class, 'destroyPhoto'])
        ->middleware('permission:appliance.edit')
        ->name('inventory.photos.destroy');

    Route::get('parts', [PartController::class, 'index'])
        ->middleware('permission:parts.view')
        ->name('parts.index');
    Route::post('parts', [PartController::class, 'store'])
        ->middleware('permission:parts.create')
        ->name('parts.store');
    Route::post('parts/import', [PartController::class, 'import'])
        ->middleware('permission:parts.create')
        ->name('parts.import');
    Route::put('parts/{part}', [PartController::class, 'update'])
        ->middleware('permission:parts.edit')
        ->name('parts.update');
    Route::delete('parts/{part}', [PartController::class, 'destroy'])
        ->middleware('permission:parts.delete')
        ->name('parts.destroy');

    Route::get('models', [ModelController::class, 'index'])
        ->middleware('permission:models.view')
        ->name('models.index');
    Route::get('models/export', [ModelController::class, 'export'])
        ->middleware('permission:models.view')
        ->name('models.export');
    Route::post('models/import-scraped', [ModelController::class, 'importScraped'])
        ->middleware('permission:models.create')
        ->name('models.import-scraped');
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
    Route::get('trucks/{truck}/appliances/export', [TruckApplianceController::class, 'export'])
        ->middleware('permission:trucks.view')
        ->name('trucks.appliances.export');
    Route::post('trucks/{truck}/appliances/import', [TruckApplianceController::class, 'import'])
        ->middleware('permission:appliance.create')
        ->name('trucks.appliances.import');
    Route::put('trucks/{truck}/appliances/{appliance}', [TruckApplianceController::class, 'update'])
        ->middleware('permission:appliance.edit')
        ->name('trucks.appliances.update');
    Route::delete('trucks/{truck}/appliances/{appliance}', [TruckApplianceController::class, 'destroy'])
        ->middleware('permission:appliance.delete')
        ->name('trucks.appliances.destroy');

    Route::resource('trucks', TruckController::class);
});
