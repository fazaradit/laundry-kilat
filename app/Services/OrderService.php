<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Logika pembuatan pesanan dan perpindahan status.
 *
 * Dipisah dari controller supaya aturan bisnis hanya ditulis sekali dan bisa
 * dipakai ulang oleh endpoint API nanti (PRD 8.1).
 */
class OrderService
{
    /**
     * Buat pesanan beserta item dan log status awal dalam satu transaksi.
     *
     * Harga satuan SELALU diambil dari master layanan, tidak pernah dari input
     * pengguna (FR-14 / BR-01). Total dihitung server (FR-15).
     *
     * @param  array{customer_id:int, diskon?:int|null, catatan?:string|null, items:array<int, array{service_id:int, qty:numeric}>}  $data
     */
    public function buatPesanan(array $data, User $kasir): Order
    {
        return DB::transaction(function () use ($data, $kasir) {
            $services = $this->ambilLayananAktif($data['items']);

            $barisItem = [];
            $subtotal = 0;
            $estimasiHari = 0;

            foreach ($data['items'] as $item) {
                $service = $services[(int) $item['service_id']];
                $qty = (float) $item['qty'];

                // Harga dari master, bukan dari request.
                $hargaSatuan = $service->harga;
                $subtotalItem = (int) round($qty * $hargaSatuan);
                $subtotal += $subtotalItem;
                $estimasiHari = max($estimasiHari, $service->estimasi_hari);

                $barisItem[] = [
                    'service_id' => $service->id,
                    'qty' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'subtotal' => $subtotalItem,
                ];
            }

            $diskon = (int) ($data['diskon'] ?? 0);

            if ($diskon > $subtotal) {
                throw ValidationException::withMessages([
                    'diskon' => 'Diskon tidak boleh melebihi subtotal (Rp '.number_format($subtotal, 0, ',', '.').').',
                ]);
            }

            $total = $subtotal - $diskon;
            $tanggalMasuk = Carbon::now();

            $order = Order::create([
                'kode_order' => $this->generateKodeOrder($tanggalMasuk),
                'customer_id' => $data['customer_id'],
                'user_id' => $kasir->id,
                'tanggal_masuk' => $tanggalMasuk,
                'estimasi_selesai' => $tanggalMasuk->copy()->startOfDay()->addDays($estimasiHari),
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total' => $total,
                'status' => OrderStatus::Diterima,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $order->items()->createMany($barisItem);

            $order->statusLogs()->create([
                'user_id' => $kasir->id,
                'status_lama' => '-',
                'status_baru' => OrderStatus::Diterima->value,
                'catatan' => 'Pesanan dibuat',
            ]);

            return $order;
        });
    }

    /**
     * Pindahkan status pesanan sesuai state machine dan catat riwayatnya.
     */
    public function ubahStatus(Order $order, OrderStatus $tujuan, User $pelaku, ?string $catatan = null): Order
    {
        $asal = $order->status;

        if ($asal->sudahFinal()) {
            throw ValidationException::withMessages([
                'status' => 'Pesanan berstatus '.$asal->label().' tidak dapat diubah lagi.',
            ]);
        }

        if (! $asal->bolehPindahKe($tujuan)) {
            throw ValidationException::withMessages([
                'status' => 'Status tidak boleh berpindah dari '.$asal->label().' ke '.$tujuan->label().'.',
            ]);
        }

        // Pembatalan hanya untuk admin (FR-26). Dijaga di sini, bukan di view,
        // karena tombol di halaman bisa diakali.
        if ($tujuan === OrderStatus::Dibatalkan && ! $pelaku->isAdmin()) {
            abort(403, 'Pembatalan pesanan hanya dapat dilakukan oleh Admin.');
        }

        return DB::transaction(function () use ($order, $asal, $tujuan, $pelaku, $catatan) {
            $order->update(['status' => $tujuan]);

            $order->statusLogs()->create([
                'user_id' => $pelaku->id,
                'status_lama' => $asal->value,
                'status_baru' => $tujuan->value,
                'catatan' => $catatan,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Ambil layanan aktif yang dipakai, sekaligus menolak layanan nonaktif
     * atau tidak dikenal (FR-11 / BR-04).
     *
     * @param  array<int, array{service_id:int, qty:numeric}>  $items
     * @return array<int, Service>
     */
    private function ambilLayananAktif(array $items): array
    {
        $ids = array_map(static fn ($item) => (int) $item['service_id'], $items);

        $services = Service::query()
            ->aktif()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id')
            ->all();

        $tidakValid = array_values(array_diff($ids, array_keys($services)));

        if ($tidakValid !== []) {
            throw ValidationException::withMessages([
                'items' => 'Ada layanan yang tidak aktif atau tidak ditemukan. Muat ulang form pesanan.',
            ]);
        }

        return $services;
    }

    /**
     * Kode pesanan berformat LDR-YYYYMMDD-NNNN dan unik per hari (FR-18).
     *
     * Nomor diambil dari kode terbesar hari itu, bukan dari jumlah baris,
     * karena jumlah bisa menabrak kode lama kalau ada pesanan yang dihapus.
     */
    public function generateKodeOrder(Carbon $tanggal): string
    {
        $prefix = 'LDR-'.$tanggal->format('Ymd').'-';

        for ($percobaan = 0; $percobaan < 3; $percobaan++) {
            $kodeTerakhir = Order::query()
                ->where('kode_order', 'like', $prefix.'%')
                ->orderByDesc('kode_order')
                ->value('kode_order');

            $urutan = 1;

            if (is_string($kodeTerakhir)) {
                $urutan = ((int) substr($kodeTerakhir, strlen($prefix))) + 1 + $percobaan;
            }

            $kode = $prefix.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);

            if (! Order::query()->where('kode_order', $kode)->exists()) {
                return $kode;
            }
        }

        // Praktis tidak tercapai; jaring pengaman supaya tidak menyimpan kode ganda.
        throw new \RuntimeException('Gagal membuat kode pesanan unik untuk tanggal '.$tanggal->toDateString().'.');
    }
}
