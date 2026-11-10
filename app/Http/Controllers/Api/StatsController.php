<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $hariIni = today();

        $pendapatanHariIni = Payment::query()
            ->whereDate('tanggal_bayar', $hariIni)
            ->sum('jumlah_bayar');

        return response()->json([
            'pesanan_hari_ini' => Order::query()->whereDate('tanggal_masuk', $hariIni)->count(),
            'pesanan_aktif' => Order::query()->whereIn('status', OrderStatus::aktifValues())->count(),
            'pendapatan_hari_ini' => (int) $pendapatanHariIni,
            'total_pelanggan' => Customer::query()->count(),
        ]);
    }
}
