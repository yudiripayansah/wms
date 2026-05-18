<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_barang' => fn() => Product::factory()->create()->kode_barang,
            'qty'         => fake()->numberBetween(0, 200),
            'location'    => fake()->optional(0.7)->lexify('LOC-????'),
            'box'         => fake()->optional(0.5)->bothify('BOX-##'),
        ];
    }
}
