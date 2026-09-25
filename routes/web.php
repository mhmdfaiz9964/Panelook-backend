<?php

use App\Http\Controllers\Api\Admin\SystemMaintenanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Emergency Browser-accessible System Maintenance Routes (Protected by secret key or Admin session)
Route::get('/system/storage-link', [SystemMaintenanceController::class, 'publicStorageLink']);
Route::get('/system/optimize-clear', [SystemMaintenanceController::class, 'publicOptimizeClear']);
Route::get('/system/status', [SystemMaintenanceController::class, 'status']);

