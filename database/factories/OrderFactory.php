<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(15, 90) * 1000;

        return [
            'kode_order' => 'LDR-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'tanggal_masuk' => now(),
            'estimasi_selesai' => now()->addDays(2)->startOfDay(),
            'subtotal' => $subtotal,
            'diskon' => 0,
            'total' => $subtotal,
            'status' => OrderStatus::Diterima,
            'catatan' => null,
        ];
    }

    /**
     * Pesanan yang sudah dibatalkan, untuk menguji status final.
     */
    public function dibatalkan(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Dibatalkan]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
