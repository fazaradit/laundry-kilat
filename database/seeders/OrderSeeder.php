<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data demo pesanan.
 *
 * Aturan konsistensi yang dijaga (PRD 14, risiko "seeder order tidak konsisten"):
 * - Pesanan berstatus diambil SELALU lunas.
 * - Pesanan berstatus diterima sebagian besar belum bayar, satu berstatus DP.
 * - Pesanan yang dibatalkan tidak punya pembayaran.
 */
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $kasir = User::query()->where('role', 'kasir')->first()
            ?? User::query()->first();

        $customers = Customer::query()->orderBy('id')->get();
        $services = Service::query()->where('status', 'aktif')->get()->keyBy('nama_layanan');

        if (! $kasir || $customers->isEmpty() || $services->isEmpty()) {
            return;
        }

        // [status, hari lalu, item (nama layanan => qty), diskon, pembayaran]
        // pembayaran: null = belum bayar, 'lunas' = bayar penuh, angka = nominal DP
        $rencana = [
            [OrderStatus::Diambil, 6, ['Cuci Kering' => 3.5, 'Setrika' => 3.5], 2000, 'lunas'],
            [OrderStatus::Diambil, 5, ['Cuci Setrika' => 4], 0, 'lunas'],
            [OrderStatus::Diambil, 5, ['Cuci Sepatu' => 2], 0, 'lunas'],
            [OrderStatus::SiapDiambil, 3, ['Cuci Kering' => 5], 0, 'lunas'],
            [OrderStatus::SiapDiambil, 2, ['Bed Cover' => 1, 'Setrika' => 2], 0, 15000],
            [OrderStatus::Disetrika, 2, ['Cuci Setrika' => 6], 5000, 20000],
            [OrderStatus::Disetrika, 1, ['Cuci Kering' => 4, 'Setrika' => 4], 0, null],
            [OrderStatus::Dicuci, 1, ['Cuci Kering' => 2.5], 0, null],
            [OrderStatus::Dicuci, 1, ['Cuci Sepatu' => 1], 0, 10000],
            [OrderStatus::Diterima, 0, ['Cuci Setrika' => 3], 0, null],
            [OrderStatus::Diterima, 0, ['Cuci Kering' => 2, 'Bed Cover' => 1], 3000, null],
            [OrderStatus::Diterima, 0, ['Setrika' => 2], 0, null],
            [OrderStatus::Diterima, 0, ['Cuci Kering' => 1.5], 0, null],
            [OrderStatus::Dibatalkan, 4, ['Cuci Setrika' => 2], 0, null],
            [OrderStatus::Dibatalkan, 3, ['Bed Cover' => 1], 0, null],
        ];

        $urutanStatus = [
            OrderStatus::Diterima->value,
            OrderStatus::Dicuci->value,
            OrderStatus::Disetrika->value,
            OrderStatus::SiapDiambil->value,
            OrderStatus::Diambil->value,
        ];

        $nomorHarian = [];

        foreach ($rencana as $index => [$status, $hariLalu, $daftarItem, $diskon, $pembayaran]) {
            $customer = $customers[$index % $customers->count()];
            $masuk = Carbon::now()->subDays($hariLalu)->setTime(8 + ($index % 9), ($index * 7) % 60);

            // kode unik per tanggal masuk
            $tanggalKode = $masuk->format('Ymd');
            $nomorHarian[$tanggalKode] = ($nomorHarian[$tanggalKode] ?? 0) + 1;
            $kode = 'LDR-'.$tanggalKode.'-'.str_pad((string) $nomorHarian[$tanggalKode], 4, '0', STR_PAD_LEFT);

            $baris = [];
            $subtotal = 0;
            $estimasiHari = 0;

            foreach ($daftarItem as $namaLayanan => $qty) {
                $service = $services->get($namaLayanan);

                if (! $service) {
                    continue;
                }

                $subtotalItem = (int) round($qty * $service->harga);
                $subtotal += $subtotalItem;
                $estimasiHari = max($estimasiHari, $service->estimasi_hari);

                $baris[] = [
                    'service_id' => $service->id,
                    'qty' => $qty,
                    'harga_satuan' => $service->harga,
                    'subtotal' => $subtotalItem,
                ];
            }

            if ($baris === []) {
                continue;
            }

            $total = $subtotal - $diskon;

            $order = Order::create([
                'kode_order' => $kode,
                'customer_id' => $customer->id,
                'user_id' => $kasir->id,
                'tanggal_masuk' => $masuk,
                'estimasi_selesai' => $masuk->copy()->startOfDay()->addDays($estimasiHari),
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total' => $total,
                'status' => $status,
                'catatan' => null,
            ]);

            $order->items()->createMany($baris);

            // Riwayat status mengikuti jalur yang dilalui, bukan lompatan.
            $jalur = $status === OrderStatus::Dibatalkan
                ? [OrderStatus::Diterima->value, OrderStatus::Dicuci->value, OrderStatus::Dibatalkan->value]
                : array_slice($urutanStatus, 0, array_search($status->value, $urutanStatus, true) + 1);

            $statusLama = '-';

            foreach ($jalur as $posisi => $statusBaru) {
                $order->statusLogs()->create([
                    'user_id' => $kasir->id,
                    'status_lama' => $statusLama,
                    'status_baru' => $statusBaru,
                    'catatan' => $posisi === 0 ? 'Pesanan dibuat' : null,
                    'created_at' => $masuk->copy()->addHours($posisi),
                    'updated_at' => $masuk->copy()->addHours($posisi),
                ]);

                $statusLama = $statusBaru;
            }

            if ($pembayaran === null) {
                continue;
            }

            $jumlahBayar = $pembayaran === 'lunas' ? $total : (int) $pembayaran;
            $jumlahBayar = min($jumlahBayar, $total);

            $order->payments()->create([
                'user_id' => $kasir->id,
                'metode' => $index % 3 === 0 ? 'transfer' : 'tunai',
                'jumlah_bayar' => $jumlahBayar,
                'kembalian' => 0,
                'catatan' => $pembayaran === 'lunas' ? 'Pelunasan' : 'Uang muka',
                'tanggal_bayar' => $masuk->copy()->addHours(1),
            ]);
        }
    }
}
