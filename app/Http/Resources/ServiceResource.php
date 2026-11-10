<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Service
 */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_layanan' => $this->nama_layanan,
            'satuan' => $this->satuan,
            'harga' => $this->harga,
            'estimasi_hari' => $this->estimasi_hari,
            'status' => $this->status,
        ];
    }
}
