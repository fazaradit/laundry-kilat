<?php

use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\ServiceController as ApiServiceController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Endpoint REST API Laundry Kilat (lihat PRD bagian 8.3). Seluruh route di
| sini otomatis mendapat prefix /api.
|
| API sengaja publik tanpa token, mengikuti cakupan materi Pertemuan 9 modul.
| Konsekuensinya endpoint tulis menerima `user_id` eksplisit di body, karena
| tidak ada sesi yang bisa dipakai untuk mengenali pelaku. Lihat API.md bagian
| Keterbatasan.
|
*/

Route::get('/services', [ApiServiceController::class, 'index']);
Route::delete('/services/{service}', [ApiServiceController::class, 'destroy']);

Route::get('/orders', [ApiOrderController::class, 'index']);
Route::post('/orders', [ApiOrderController::class, 'store']);

// Detail memakai batasan angka supaya "/orders/abc" tidak diteruskan ke
// findOrFail dan berakhir 404 yang membingungkan.
Route::get('/orders/{id}', [ApiOrderController::class, 'show'])->whereNumber('id');

Route::put('/orders/{order}/status', [ApiOrderController::class, 'updateStatus']);
Route::post('/orders/{order}/payments', [ApiOrderController::class, 'storePayment']);

Route::get('/stats', [StatsController::class, 'index']);
