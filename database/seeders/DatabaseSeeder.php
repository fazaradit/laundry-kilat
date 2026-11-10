<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Urutan mengikuti ketergantungan foreign key: User sebelum Service,
     * Service sebelum Customer/Order, dan seterusnya.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ServiceSeeder::class,
            CustomerSeeder::class,
        ]);
    }
}
