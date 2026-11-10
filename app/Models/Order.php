<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'kode_order',
        'customer_id',
        'user_id',
        'tanggal_masuk',
        'estimasi_selesai',
        'subtotal',
        'diskon',
        'total',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'datetime',
            'estimasi_selesai' => 'date',
            'subtotal' => 'integer',
            'diskon' => 'integer',
            'total' => 'integer',
            'status' => OrderStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Kasir yang menerima pesanan.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<OrderStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest();
    }

    /**
     * Total yang sudah dibayar. Selalu dihitung dari tabel payments (BR-03).
     */
    public function getDibayarAttribute(): int
    {
        if (array_key_exists('dibayar', $this->attributes)) {
            return (int) $this->attributes['dibayar'];
        }

        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->sum('jumlah_bayar');
        }

        return (int) $this->payments()->sum('jumlah_bayar');
    }

    public function getSisaBayarAttribute(): int
    {
        return max(0, $this->total - $this->dibayar);
    }

    /**
     * Status pelunasan diturunkan, tidak disimpan (PRD 6.4 no. 3).
     */
    public function getStatusPelunasanAttribute(): string
    {
        $dibayar = $this->dibayar;

        return match (true) {
            $dibayar <= 0 => 'Belum Bayar',
            $dibayar < $this->total => 'DP',
            default => 'Lunas',
        };
    }

    public function sudahLunas(): bool
    {
        return $this->dibayar >= $this->total;
    }

    public function sudahFinal(): bool
    {
        return $this->status->sudahFinal();
    }
}
