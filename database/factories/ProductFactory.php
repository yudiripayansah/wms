<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_barang' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{5}'),
            'brand'       => fake()->company(),
            'barcode'     => fake()->ean13(),
            'sku'         => fake()->bothify('SKU-####'),
            'nama_barang' => fake()->words(3, true),
            'colour'      => fake()->safeColorName(),
            'size'        => fake()->randomElement(['S', 'M', 'L', 'XL', 'XXL']),
            'price'       => fake()->randomFloat(2, 10000, 500000),
        ];
    }
}
