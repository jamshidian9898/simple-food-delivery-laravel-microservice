<?php

namespace Database\Seeders;

use App\Infrastructure\Services\ProductDataPublisher;
use App\Models\Basket;
use App\Models\Restaurant;
use App\Enums\Basket\BasketStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BasketSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed deterministic test baskets and additional random baskets, then publish them for cross-service access.
     *
     * Creates a fixed set of test baskets using simulated user UUIDs (upserting by `user_id` and `restaurant_id`),
     * generates a batch of additional random baskets via the factory, publishes created baskets through
     * ProductDataPublisher, and logs a summary. Exits early and logs a warning if no restaurants exist.
     */
    public function run(): void
    {
        $publisher = new ProductDataPublisher();
        $createdBaskets = collect();
        
        $this->command->info('🛒 Starting Basket Seeding...');

        // Get restaurants for baskets
        $restaurants = Restaurant::all();
        
        if ($restaurants->isEmpty()) {
            $this->command->warn('No restaurants found. Please run RestaurantSeeder first.');
            return;
        }

        // Simulate user IDs from user service (these would come from Redis in real scenario)
        $simulatedUserIds = [
            '019a5bf6-16b5-7290-8413-0a6bb37fe14a', // Admin Customer
            '019a5bf6-16b5-7290-8413-0a6bb37fe14b', // Jane Smith
            '019a5bf6-16b5-7290-8413-0a6bb37fe14c', // Mike Johnson
            '019a5bf6-16b5-7290-8413-0a6bb37fe14d', // Test Customer 1
            '019a5bf6-16b5-7290-8413-0a6bb37fe14e', // Test Customer 2
        ];

        // Create test baskets with known data
        $testBaskets = [
            [
                'user_id' => $simulatedUserIds[0],
                'restaurant_id' => $restaurants->where('name', 'Pizza Palace')->first()?->id ?? $restaurants->first()->id,
                'status' => BasketStatus::active,
            ],
            [
                'user_id' => $simulatedUserIds[1],
                'restaurant_id' => $restaurants->where('name', 'Burger Kingdom')->first()?->id ?? $restaurants->first()->id,
                'status' => BasketStatus::active,
            ],
            [
                'user_id' => $simulatedUserIds[2],
                'restaurant_id' => $restaurants->where('name', 'Sushi Master')->first()?->id ?? $restaurants->first()->id,
                'status' => BasketStatus::ordered,
            ],
            [
                'user_id' => $simulatedUserIds[3],
                'restaurant_id' => $restaurants->where('name', 'Taco Fiesta')->first()?->id ?? $restaurants->first()->id,
                'status' => BasketStatus::expired,
            ],
            [
                'user_id' => $simulatedUserIds[4],
                'restaurant_id' => $restaurants->where('name', 'Indian Spice House')->first()?->id ?? $restaurants->first()->id,
                'status' => BasketStatus::active,
            ],
        ];

        foreach ($testBaskets as $basketData) {
            $basket = Basket::firstOrCreate(
                [
                    'user_id' => $basketData['user_id'],
                    'restaurant_id' => $basketData['restaurant_id']
                ],
                $basketData
            );
            $createdBaskets->push($basket);
            $publisher->publishBasket($basket);
        }

        // Create additional random baskets
        $additionalBaskets = collect();
        for ($i = 0; $i < 15; $i++) {
            // Generate more simulated user IDs for variety
            $randomUserId = Str::uuid();
            $randomRestaurant = $restaurants->random();
            
            $basket = Basket::factory()->create([
                'user_id' => $randomUserId,
                'restaurant_id' => $randomRestaurant->id,
            ]);
            $additionalBaskets->push($basket);
        }
        
        $createdBaskets = $createdBaskets->merge($additionalBaskets);
        $publisher->publishBaskets($additionalBaskets);

        $testBasketsCount = count($testBaskets);
        $totalBaskets = $testBasketsCount + $additionalBaskets->count();

        $this->command->info("✅ Ensured {$totalBaskets} baskets ({$testBasketsCount} test + {$additionalBaskets->count()} random)");
        $this->command->info('   Test Baskets:');
        $this->command->info('   - Active basket for Pizza Palace');
        $this->command->info('   - Active basket for Burger Kingdom');
        $this->command->info('   - Ordered basket for Sushi Master');
        $this->command->info('   - Expired basket for Taco Fiesta');
        $this->command->info('   - Active basket for Indian Spice House');
        $this->command->info('📡 Published ' . $createdBaskets->count() . ' baskets to Redis for cross-service access');
    }
}
