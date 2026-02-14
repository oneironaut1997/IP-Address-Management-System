<?php

namespace Database\Factories;

use App\Models\IPHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IPHistory>
 */
class IPHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IPHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'ip_address_id' => fake()->uuid(),
            'modified_by' => fake()->uuid(),
            'old_values' => null,
            'new_values' => [
                'ip_address' => fake()->ipv4(),
                'label' => fake()->words(3, true),
            ],
            'action' => 'created',
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the action is an update.
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'old_values' => [
                'ip_address' => fake()->ipv4(),
                'label' => 'Old Label',
            ],
            'new_values' => [
                'ip_address' => fake()->ipv4(),
                'label' => 'New Label',
            ],
            'action' => 'updated',
        ]);
    }

    /**
     * Indicate that the action is a deletion.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'old_values' => [
                'ip_address' => fake()->ipv4(),
                'label' => fake()->words(3, true),
            ],
            'new_values' => null,
            'action' => 'deleted',
        ]);
    }
}