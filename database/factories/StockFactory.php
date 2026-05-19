<?php

namespace Database\Factories;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barcode'  => fn() => Inventory::factory()->create()->barcode,
            'qty'      => fake()->numberBetween(0, 200),
            'location' => fake()->optional(0.7)->lexify('LOC-????'),
            'bin'      => fake()->optional(0.5)->bothify('BIN-##'),
        ];
    }
}
