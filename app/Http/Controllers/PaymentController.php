<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function store(StorePaymentRequest $request, Order $order): RedirectResponse
    {
        $pembayaran = $this->paymentService->catatPembayaran(
            $order,
            $request->validated(),
            $request->user()
        );

        $pesan = 'Pembayaran Rp '.number_format($pembayaran->jumlah_bayar, 0, ',', '.').' tercatat.';

        if ($pembayaran->kembalian > 0) {
            $pesan .= ' Kembalian Rp '.number_format($pembayaran->kembalian, 0, ',', '.').'.';
        }

        return redirect()->route('orders.show', $order)->with('success', $pesan);
    }
}
