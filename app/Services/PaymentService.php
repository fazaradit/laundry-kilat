<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pencatatan pembayaran, termasuk pembayaran bertahap (DP lalu pelunasan).
 *
 * Controller dan FormRequest sudah menyaring input yang jelas salah, tetapi
 * aturan uang tetap ditegakkan ulang di sini. Alasannya: keputusan "boleh
 * dibayar berapa" bergantung pada isi database saat itu, bukan pada bentuk
 * form. Baris pesanan dikunci selama transaksi supaya dua kasir yang menekan
 * tombol hampir bersamaan tidak bisa melunasi tagihan yang sama dua kali.
 */
class PaymentService
{
    /**
     * @param  array{metode:string, jumlah_bayar:int, uang_diterima?:int|null, catatan?:string|null}  $data
     */
    public function catatPembayaran(Order $order, array $data, User $kasir): Payment
    {
        return DB::transaction(function () use ($order, $data, $kasir) {
            $terkunci = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $jumlah = (int) $data['jumlah_bayar'];

            if ($terkunci->status === OrderStatus::Dibatalkan) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Pesanan yang dibatalkan tidak bisa menerima pembayaran.',
                ]);
            }

            // Dibaca setelah baris dikunci, jadi nilainya sudah final.
            $sisa = $terkunci->sisa_bayar;

            if ($sisa <= 0) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Pesanan ini sudah lunas.',
                ]);
            }

            if ($jumlah > $sisa) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Jumlah bayar melebihi sisa tagihan (Rp '.number_format($sisa, 0, ',', '.').').',
                ]);
            }

            $tunai = $data['metode'] === 'tunai';
            $uangDiterima = $tunai ? (int) ($data['uang_diterima'] ?? 0) : null;

            if ($tunai && $uangDiterima < $jumlah) {
                throw ValidationException::withMessages([
                    'uang_diterima' => 'Uang diterima kurang dari jumlah bayar.',
                ]);
            }

            return $terkunci->payments()->create([
                'user_id' => $kasir->id,
                'metode' => $data['metode'],
                'jumlah_bayar' => $jumlah,
                'uang_diterima' => $uangDiterima,
                // Kembalian hanya bermakna untuk tunai (FR-30).
                'kembalian' => $tunai ? $uangDiterima - $jumlah : 0,
                'catatan' => $data['catatan'] ?? null,
                'tanggal_bayar' => now(),
            ]);
        });
    }
}
