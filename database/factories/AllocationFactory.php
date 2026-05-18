<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AllocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'    => null,
            'session_id' => fake()->unique()->uuid(),
            'status'     => 'DRAFT',
            'remarks'    => fake()->optional(0.4)->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'CONFIRMED']);
    }

    public function processed(): static
    {
        return $this->state(['status' => 'PROCESSED']);
    }
}
