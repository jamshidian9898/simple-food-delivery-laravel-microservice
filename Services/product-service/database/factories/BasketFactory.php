<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Basket>
 */
class BasketFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \App\Models\Basket::class;
    /**
     * Define default attributes for a Basket model.
     *
     * @return array<string, mixed> Associative array of attribute names to their default values:
     *                              - `user_id`: integer user identifier
     *                              - `restaurant_id`: a Restaurant factory instance for association
     *                              - `status`: a BasketStatus enum value
     */
    public function definition(): array
    {
        return [
            'user_id' => rand(1, 2),
            'restaurant_id' => \App\Models\Restaurant::factory(),
            'status' => \App\Enums\Basket\BasketStatus::active,
        ];
    }
}
