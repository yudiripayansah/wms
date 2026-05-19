<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barcode' => fake()->unique()->ean13(),
            'brand'   => fake()->company(),
            'sku'     => fake()->bothify('SKU-####'),
            'article' => fake()->words(3, true),
            'color'   => fake()->safeColorName(),
            'size'    => fake()->randomElement(['37', '38', '39', '40', '41', '42', '43', '44']),
        ];
    }
}
