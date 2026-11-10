<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

// ---------- Public ----------
Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------- Protected ----------
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('customers', CustomerController::class)->except(['show']);

    // Laporan harus didaftarkan SEBELUM resource, kalau tidak "/orders/report"
    // akan tertangkap sebagai {order} dan berakhir 404.
    Route::get('/orders/report', [OrderController::class, 'report'])->name('orders.report');

    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/orders/{order}/struk', [OrderController::class, 'struk'])->name('orders.struk');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');

    // Hanya Admin (FR-12 / AC-02).
    Route::middleware(['admin'])->group(function () {
        Route::resource('services', ServiceController::class)->except(['show']);
        Route::put('/orders/{order}/batalkan', [OrderController::class, 'batalkan'])->name('orders.batalkan');
    });
});
