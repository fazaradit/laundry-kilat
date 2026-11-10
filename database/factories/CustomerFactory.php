<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->firstName().' '.fake()->lastName(),
            'nomor_telepon' => fake()->numerify('08##########'),
            'alamat' => fake()->address(),
        ];
    }
}
