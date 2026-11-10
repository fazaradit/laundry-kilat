<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk response mengikuti PRD 8.4.
 *
 * `dibayar` dan `sisa_bayar` memakai accessor Order, kecuali query pemanggil
 * sudah menyertakan withSum('payments as dibayar'), yang membuat accessor
 * memakai angka hasil agregat tanpa query tambahan.
 *
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_order' => $this->kode_order,
            'customer' => [
                'id' => $this->customer->id,
                'nama' => $this->customer->nama,
                'nomor_telepon' => $this->customer->nomor_telepon,
            ],
            'petugas' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'tanggal_masuk' => $this->tanggal_masuk?->format('Y-m-d H:i:s'),
            'estimasi_selesai' => $this->estimasi_selesai?->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'diskon' => $this->diskon,
            'total' => $this->total,
            'dibayar' => $this->dibayar,
            'sisa_bayar' => $this->sisa_bayar,
            'status_pelunasan' => $this->status_pelunasan,
            'status' => $this->status->value,
            'items' => $this->items->map(fn ($item) => [
                'service' => $item->service->nama_layanan,
                'satuan' => $item->service->satuan,
                'qty' => $item->qty,
                'harga_satuan' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
            ])->all(),
        ];
    }
}
