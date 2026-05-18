<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_id'  => fake()->uuid(),
            'kode_barang' => fn() => Product::factory()->create()->kode_barang,
            'qty'         => fake()->numberBetween(1, 50),
            'location'    => fake()->optional(0.7)->lexify('LOC-????'),
            'box'         => fake()->optional(0.5)->bothify('BOX-##'),
            'status'      => 'OK',
            'type'        => fake()->randomElement(['IN', 'OUT', 'OPNAME', 'ADJUSTMENT']),
            'remarks'     => fake()->optional(0.3)->sentence(),
        ];
    }

    public function in(): static
    {
        return $this->state(['type' => 'IN']);
    }

    public function out(): static
    {
        return $this->state(['type' => 'OUT']);
    }

    public function opname(): static
    {
        return $this->state(['type' => 'OPNAME']);
    }

    public function adjustment(): static
    {
        return $this->state(['type' => 'ADJUSTMENT']);
    }

    public function declined(): static
    {
        return $this->state(['status' => 'DECLINED']);
    }
}
