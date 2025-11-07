<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password123'),
            'type' => fake()->randomElement(['customer', 'restaurant', 'courier']),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a customer user.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer',
            'name' => fake()->name(),
        ]);
    }

    /**
     * Create a restaurant user.
     */
    public function restaurant(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'restaurant',
            'name' => fake()->company() . ' Restaurant',
            'phone' => fake()->phoneNumber(),
        ]);
    }

    /**
     * Create a courier user.
     */
    public function courier(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'courier',
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(), // Couriers always need phone
        ]);
    }
}
