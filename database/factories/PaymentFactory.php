<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'metode' => 'tunai',
            'jumlah_bayar' => 20000,
            'kembalian' => 0,
            'catatan' => null,
            'tanggal_bayar' => now(),
        ];
    }
}
