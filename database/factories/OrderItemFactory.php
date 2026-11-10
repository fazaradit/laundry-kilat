<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 5);
        $harga = 7000;

        return [
            'order_id' => Order::factory(),
            'service_id' => Service::factory(),
            'qty' => $qty,
            'harga_satuan' => $harga,
            'subtotal' => (int) round($qty * $harga),
        ];
    }
}
