<?php

namespace Database\Seeders;

use App\Infrastructure\Services\ProductDataPublisher;
use App\Models\Restaurant;
use App\Enums\Restaurant\RestaurantStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed restaurant data with known test restaurants and random ones.
     */
    public function run(): void
    {
        $publisher = new ProductDataPublisher();
        $createdRestaurants = collect();
        
        $this->command->info('🏪 Starting Restaurant Seeding...');

        // Create test restaurants with known data
        $testRestaurants = [
            [
                'name' => 'Pizza Palace',
                'description' => 'Authentic Italian pizzas made with fresh ingredients and traditional recipes.',
                'address' => '123 Main Street, Downtown, City Center',
                'status' => RestaurantStatus::active,
            ],
            [
                'name' => 'Burger Kingdom',
                'description' => 'Premium burgers and fries with locally sourced ingredients.',
                'address' => '456 Oak Avenue, Food District, Metro Area',
                'status' => RestaurantStatus::active,
            ],
            [
                'name' => 'Sushi Master',
                'description' => 'Fresh sushi and Japanese cuisine prepared by expert chefs.',
                'address' => '789 Pine Road, Asian Quarter, Downtown',
                'status' => RestaurantStatus::active,
            ],
            [
                'name' => 'Taco Fiesta',
                'description' => 'Authentic Mexican tacos, burritos, and traditional dishes.',
                'address' => '321 Elm Street, Latin District, City South',
                'status' => RestaurantStatus::active,
            ],
            [
                'name' => 'Indian Spice House',
                'description' => 'Traditional Indian curry, biryani, and aromatic spices.',
                'address' => '654 Maple Lane, Spice Market, East Side',
                'status' => RestaurantStatus::active,
            ],
            [
                'name' => 'Closed Restaurant',
                'description' => 'Currently closed for renovations.',
                'address' => '999 Closed Street, Renovation District',
                'status' => RestaurantStatus::deactive,
            ],
        ];

        foreach ($testRestaurants as $restaurantData) {
            $restaurant = Restaurant::firstOrCreate(
                ['name' => $restaurantData['name']],
                $restaurantData
            );
            $createdRestaurants->push($restaurant);
            $publisher->publishRestaurant($restaurant);
        }

        // Create additional random restaurants
        $randomRestaurants = Restaurant::factory()
            ->count(14) // Total 20 restaurants (6 test + 14 random)
            ->create();
        
        $createdRestaurants = $createdRestaurants->merge($randomRestaurants);
        $publisher->publishRestaurants($randomRestaurants);

        $this->command->info('✅ Ensured 20 restaurants (6 test + 14 random)');
        $this->command->info('   - Pizza Palace (active)');
        $this->command->info('   - Burger Kingdom (active)');
        $this->command->info('   - Sushi Master (active)');
        $this->command->info('   - Taco Fiesta (active)');
        $this->command->info('   - Indian Spice House (active)');
        $this->command->info('   - Closed Restaurant (deactive)');
        $this->command->info('📡 Published ' . $createdRestaurants->count() . ' restaurants to Redis for cross-service access');
    }
}
