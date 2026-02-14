<?php

namespace Database\Factories;

use App\Models\IPAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IPAddress>
 */
class IPAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IPAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'label' => fake()->words(3, true),
            'comment' => fake()->sentence(),
            'type' => 'ipv4',
        ];
    }

    /**
     * Indicate that the IP address is IPv6.
     */
    public function ipv6(): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => fake()->ipv6(),
            'type' => 'ipv6',
        ]);
    }
}