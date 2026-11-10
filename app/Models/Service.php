<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'nama_layanan',
        'satuan',
        'harga',
        'estimasi_hari',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'estimasi_hari' => 'integer',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Layanan yang boleh dipakai pada pesanan baru (FR-11 / BR-04).
     *
     * @param  Builder<Service>  $query
     */
    public function scopeAktif(Builder $query): void
    {
        $query->where('status', 'aktif');
    }

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }
}
