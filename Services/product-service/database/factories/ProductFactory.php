<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \App\Models\Product::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => \App\Models\Restaurant::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->slug(),
            'description' => fake()->sentence(12),
            // store prices as integers (cents)
            'price' => fake()->numberBetween(500, 20000),
            'image_url' => fake()->imageUrl(640, 480, 'food', true),
            'is_available' => fake()->boolean(80),
            'quantity' => fake()->numberBetween(0, 100),
            'estimated_preparation_time' => fake()->numberBetween(5, 60),
        ];
    }
}
