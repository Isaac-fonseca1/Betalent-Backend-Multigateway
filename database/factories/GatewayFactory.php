<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gateway>
 */
class GatewayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Gateway ' . $this->faker->company(),
            'driver' => $this->faker->unique()->slug(),
            'priority' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
