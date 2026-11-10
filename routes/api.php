<?php

use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Endpoint REST API Laundry Kilat (lihat PRD bagian 8.3). Seluruh route di
| sini otomatis mendapat prefix /api. API sengaja publik tanpa token,
| mengikuti cakupan materi Pertemuan 9 modul.
|
*/

Route::get('/stats', [StatsController::class, 'index']);
