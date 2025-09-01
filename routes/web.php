<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ToolsController;

Route::get('/', function () {
    return view('geospatial-dashboard');
});

Route::get('/dashboard', function () {
    return view('geospatial-dashboard');
});

// Rutas para el sistema de reportes LLM
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/create', [ReportController::class, 'create'])->name('create');
    Route::post('/', [ReportController::class, 'store'])->name('store');
    Route::get('/{report}', [ReportController::class, 'show'])->name('show');
    Route::post('/{report}/regenerate', [ReportController::class, 'regenerate'])->name('regenerate');
    Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy');
    Route::get('/{report}/export', [ReportController::class, 'export'])->name('export');
});

// Rutas para herramientas
Route::prefix('tools')->name('tools.')->group(function () {
    Route::get('/maps', [ToolsController::class, 'maps'])->name('maps');
    Route::get('/weather', [ToolsController::class, 'weather'])->name('weather');
    Route::get('/analytics', [ToolsController::class, 'analytics'])->name('analytics');
});
