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
         * Generate the factory's default attribute array for a User model.
         *
         * The generated attributes include a cached hashed password (hash of 'password123'),
         * a random `type` selected from ['customer', 'restaurant', 'courier'], and an
         * optional `phone` present with 80% probability. Other standard fields like
         * `name`, `email`, `email_verified_at`, and `remember_token` are also populated.
         *
         * @return array<string, mixed> The default attribute values for the model.
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
     * Mark the model's email address as unverified.
     *
     * Sets the generated attributes so `email_verified_at` is `null`.
     *
     * @return static The factory instance with the unverified state applied.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Apply a factory state for a customer user.
     *
     * Sets the `type` attribute to `'customer'` and assigns a random person name to `name`.
     *
     * @return static The factory instance with the customer state applied.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer',
            'name' => fake()->name(),
        ]);
    }

    /**
     * Configure the factory to generate a restaurant user.
     *
     * @return static The factory instance with state configured for a restaurant user.
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
     * Configure the factory to produce a courier user.
     *
     * @return static The factory instance with `type` set to `'courier'`, a random `name`, and a generated `phone`.
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
