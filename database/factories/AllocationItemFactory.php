<?php

namespace Database\Factories;

use App\Models\Allocation;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class AllocationItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'allocation_id' => Allocation::factory(),
            'kode_barang'   => fn() => Product::factory()->create()->kode_barang,
            'qty'           => fake()->numberBetween(1, 20),
            'location'      => fake()->optional(0.7)->lexify('LOC-????'),
            'box'           => fake()->optional(0.5)->bothify('BOX-##'),
        ];
    }
}
