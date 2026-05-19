<?php

namespace Database\Factories;

use App\Models\Allocation;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

class AllocationItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'allocation_id' => Allocation::factory(),
            'barcode'       => fn() => Inventory::factory()->create()->barcode,
            'qty'           => fake()->numberBetween(1, 20),
            'location'      => fake()->optional(0.7)->lexify('LOC-????'),
            'bin'           => fake()->optional(0.5)->bothify('BIN-##'),
        ];
    }
}
