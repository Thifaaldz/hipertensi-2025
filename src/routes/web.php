<?php

use App\Http\Controllers\MlController;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Illuminate\Support\Facades\Response;

/* NOTE: Do Not Remove
/ Livewire asset handling if using sub folder in domain
*/
Livewire::setUpdateRoute(function ($handle) {
    return Route::post(config('app.asset_prefix') . '/livewire/update', $handle);
});

Livewire::setScriptRoute(function ($handle) {
    return Route::get(config('app.asset_prefix') . '/livewire/livewire.js', $handle);
});
/*
/ END
*/

//Route::get('/', function () {
//    return view('welcome');
//});

use App\Http\Livewire\HomeMap;
use App\Livewire\HomePage;

Route::get('/', HomePage::class)->name('home');


// ✅ API ML diletakkan di luar route `/`
Route::middleware(['filament'])->group(function () {
    Route::get('/admin/api/ml/geojson', [MlController::class, 'geojson'])->name('admin.ml.geojson');
    Route::get('/admin/api/ml/predictions', [MlController::class, 'predictionsJson'])->name('admin.ml.predictions');
});
