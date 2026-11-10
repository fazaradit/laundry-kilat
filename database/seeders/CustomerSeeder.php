<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (Customer::query()->exists()) {
            return;
        }

        Customer::factory()->count(12)->create();
    }
}
