<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $satuan = fake()->randomElement(['kg', 'pcs']);

        return [
            'nama_layanan' => ucfirst(fake()->words(2, true)),
            'satuan' => $satuan,
            'harga' => $satuan === 'kg'
                ? fake()->numberBetween(5, 15) * 1000
                : fake()->numberBetween(15, 40) * 1000,
            'estimasi_hari' => fake()->numberBetween(1, 4),
            'status' => 'aktif',
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['status' => 'nonaktif']);
    }
}
