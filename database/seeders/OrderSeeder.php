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
 * - Pesanan berstatus selesai umumnya lunas. Satu dibiarkan DP supaya terlihat
 *   bahwa status pengerjaan dan status pelunasan adalah dua hal berbeda:
 *   cucian sudah selesai, tetapi pelanggan belum melunasi sisanya.
 * - Pesanan berstatus diterima sebagian besar belum bayar, satu berstatus DP
 *   dan dibayar hari ini supaya Dashboard punya angka saat demo dibuka.
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
            [OrderStatus::Selesai, 6, ['Cuci Kering' => 3.5, 'Setrika' => 3.5], 2000, 'lunas'],
            [OrderStatus::Selesai, 5, ['Cuci Setrika' => 4], 0, 'lunas'],
            [OrderStatus::Selesai, 5, ['Cuci Sepatu' => 2], 0, 'lunas'],
            [OrderStatus::Selesai, 3, ['Cuci Kering' => 5], 0, 'lunas'],
            // Selesai dikerjakan, tetapi baru dibayar sebagian. Dipakai saat demo
            // untuk menjelaskan bahwa status pesanan dan status pelunasan terpisah.
            [OrderStatus::Selesai, 2, ['Bed Cover' => 1, 'Setrika' => 2], 0, 15000],
            [OrderStatus::Diproses, 2, ['Cuci Setrika' => 6], 5000, 20000],
            [OrderStatus::Diproses, 1, ['Cuci Kering' => 4, 'Setrika' => 4], 0, null],
            [OrderStatus::Diproses, 1, ['Cuci Kering' => 2.5], 0, null],
            [OrderStatus::Diproses, 1, ['Cuci Sepatu' => 1], 0, 10000],
            [OrderStatus::Diterima, 0, ['Cuci Setrika' => 3], 0, null],
            // DP hari ini: satu-satunya sumber "pendapatan hari ini" di Dashboard
            // saat data baru di-seed, supaya demo tidak dibuka dengan angka nol.
            [OrderStatus::Diterima, 0, ['Cuci Kering' => 2, 'Bed Cover' => 1], 3000, 15000],
            [OrderStatus::Diterima, 0, ['Setrika' => 2], 0, null],
            [OrderStatus::Diterima, 0, ['Cuci Kering' => 1.5], 0, null],
            [OrderStatus::Dibatalkan, 4, ['Cuci Setrika' => 2], 0, null],
            [OrderStatus::Dibatalkan, 3, ['Bed Cover' => 1], 0, null],
        ];

        $urutanStatus = [
            OrderStatus::Diterima->value,
            OrderStatus::Diproses->value,
            OrderStatus::Selesai->value,
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
                ? [OrderStatus::Diterima->value, OrderStatus::Diproses->value, OrderStatus::Dibatalkan->value]
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

            $tunai = $index % 3 !== 0;

            // Tunai diserahkan dalam pecahan uang nyata, jadi sebagian baris
            // demo punya kembalian bukan nol. Transfer tidak punya uang fisik.
            $uangDiterima = $tunai ? (int) (ceil($jumlahBayar / 50000) * 50000) : null;

            $order->payments()->create([
                'user_id' => $kasir->id,
                'metode' => $tunai ? 'tunai' : 'transfer',
                'jumlah_bayar' => $jumlahBayar,
                'uang_diterima' => $uangDiterima,
                'kembalian' => $tunai ? $uangDiterima - $jumlahBayar : 0,
                'catatan' => $pembayaran === 'lunas' ? 'Pelunasan' : 'Uang muka',
                'tanggal_bayar' => $masuk->copy()->addHours(1),
            ]);
        }
    }
}
