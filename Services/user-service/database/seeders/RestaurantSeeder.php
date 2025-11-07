<?php

namespace Database\Seeders;

use App\Infrastructure\Services\UserDataPublisher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RestaurantSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed restaurant users.
     */
    public function run(): void
    {
        $publisher = new UserDataPublisher();
        $createdUsers = collect();

        // Create test restaurants with known credentials
        $testRestaurants = [
            [
                'name' => 'Pizza Palace Restaurant',
                'email' => 'restaurant@example.com',
                'type' => 'restaurant',
                'phone' => '+1234567800',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Burger Kingdom Restaurant',
                'email' => 'burger.restaurant@example.com',
                'type' => 'restaurant',
                'phone' => '+1234567801',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sushi Master Restaurant',
                'email' => 'sushi.restaurant@example.com',
                'type' => 'restaurant',
                'phone' => '+1234567802',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Taco Fiesta Restaurant',
                'email' => 'taco.restaurant@example.com',
                'type' => 'restaurant',
                'phone' => '+1234567803',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Indian Spice Restaurant',
                'email' => 'indian.restaurant@example.com',
                'type' => 'restaurant',
                'phone' => '+1234567804',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($testRestaurants as $restaurantData) {
            $restaurantData = User::factory()->restaurant()->make($restaurantData)->toArray();

            // Ensure password is always present
            if (!isset($restaurantData['password'])) {
                $restaurantData['password'] = Hash::make('password123');
            }

            $user = User::firstOrCreate(
                ['email' => $restaurantData['email'], 'type' => $restaurantData['type']],
                $restaurantData
            );
            $createdUsers->push($user);
            $publisher->publishUser($user);
        }

        // Create additional random restaurants
        $randomRestaurants = User::factory()
            ->restaurant()
            ->count(15) // Total 20 restaurants (5 test + 15 random)
            ->create();

        $createdUsers = $createdUsers->merge($randomRestaurants);
        $publisher->publishUsers($randomRestaurants);

        $this->command->info('✅ Ensured 20 restaurant users (5 test + 15 random)');
        $this->command->info('📡 Published ' . $createdUsers->count() . ' restaurants to Redis for cross-service access');
    }
}
