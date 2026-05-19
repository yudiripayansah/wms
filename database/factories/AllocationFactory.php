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
            'status'     => 'PENDING',
            'remarks'    => fake()->optional(0.4)->sentence(),
        ];
    }

    public function processing(): static
    {
        return $this->state(['status' => 'PROCESSING']);
    }

    public function finished(): static
    {
        return $this->state(['status' => 'FINISHED']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'COMPLETED']);
    }
}
