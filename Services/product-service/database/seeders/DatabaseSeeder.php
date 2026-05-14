<?php

namespace Database\Seeders;

use App\Infrastructure\Services\ProductDataPublisher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Orchestrates database seeding for the Product Service by executing seeders in dependency order and printing console summaries.
     *
     * After running the seeders, prints completion messages and displays both relational database and Redis-based seeding statistics.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🏪 Starting Product Service Database Seeding...');

        // Seed in logical dependency order
        $this->call([
            RestaurantSeeder::class,     // Restaurants first (products depend on them)
            ProductSeeder::class,        // Products second (basket items depend on them)
            BasketSeeder::class,         // Baskets third (basket items depend on them)
            BasketItemSeeder::class,     // Basket items last
        ]);

        $this->command->info('');
        $this->command->info('🎉 Product Service Database Seeding Completed!');
        
        // Show comprehensive statistics
        $this->displaySeedingStatistics();
        
        // Show Redis cross-service data statistics
        $this->displayRedisStatistics();
    }

    /**
     * Print a formatted report of seeding results to the seeder console.
     *
     * The report includes total and categorical counts for Restaurants (active/deactive),
     * Products (available/unavailable), Baskets (active/ordered/expired), Basket Items,
     * and a grand total of all created records.
     */
    private function displaySeedingStatistics(): void
    {
        $restaurantCount = \App\Models\Restaurant::count();
        $activeRestaurants = \App\Models\Restaurant::where('status', 'active')->count();
        $deactiveRestaurants = \App\Models\Restaurant::where('status', 'deactive')->count();
        
        $productCount = \App\Models\Product::count();
        $availableProducts = \App\Models\Product::where('is_available', true)->count();
        $unavailableProducts = \App\Models\Product::where('is_available', false)->count();
        
        $basketCount = \App\Models\Basket::count();
        $activeBaskets = \App\Models\Basket::where('status', 'active')->count();
        $orderedBaskets = \App\Models\Basket::where('status', 'ordered')->count();
        $expiredBaskets = \App\Models\Basket::where('status', 'expired')->count();
        
        $basketItemCount = \App\Models\BasketItem::count();
        
        $this->command->info('📊 Total Records Created:');
        $this->command->info("   - {$restaurantCount} Restaurants ({$activeRestaurants} active, {$deactiveRestaurants} deactive)");
        $this->command->info("   - {$productCount} Products ({$availableProducts} available, {$unavailableProducts} unavailable)");
        $this->command->info("   - {$basketCount} Baskets ({$activeBaskets} active, {$orderedBaskets} ordered, {$expiredBaskets} expired)");
        $this->command->info("   - {$basketItemCount} Basket Items");
        $this->command->info("   - Total: " . ($restaurantCount + $productCount + $basketCount + $basketItemCount) . " records");
    }

    /**
     * Print Redis-based cross-service metrics and a brief integration summary to the console.
     *
     * Retrieves cross-service statistics from Redis and outputs a formatted report for
     * Restaurants, Products, Baskets, and Basket Items. Missing statistic keys are treated
     * as zero when displayed. Also prints a short summary describing which services consume
     * the published data.
     */
    private function displayRedisStatistics(): void
    {
        $publisher = new ProductDataPublisher();
        $stats = $publisher->getStatistics();
        
        $this->command->info('');
        $this->command->info('📡 Redis Cross-Service Data:');
        $this->command->info('   Restaurants:');
        $this->command->info('   - Total: ' . ($stats['total_restaurants'] ?? 0));
        $this->command->info('   - Active: ' . ($stats['active_restaurants'] ?? 0));
        $this->command->info('   - Deactive: ' . ($stats['deactive_restaurants'] ?? 0));
        
        $this->command->info('   Products:');
        $this->command->info('   - Total: ' . ($stats['total_products'] ?? 0));
        $this->command->info('   - Available: ' . ($stats['available_products'] ?? 0));
        $this->command->info('   - Unavailable: ' . ($stats['unavailable_products'] ?? 0));
        
        $this->command->info('   Baskets:');
        $this->command->info('   - Total: ' . ($stats['total_baskets'] ?? 0));
        $this->command->info('   - Active: ' . ($stats['active_baskets'] ?? 0));
        $this->command->info('   - Ordered: ' . ($stats['ordered_baskets'] ?? 0));
        $this->command->info('   - Expired: ' . ($stats['expired_baskets'] ?? 0));
        
        $this->command->info('   Basket Items: ' . ($stats['total_basket_items'] ?? 0));
        
        $this->command->info('');
        $this->command->info('🔗 Cross-Service Integration:');
        $this->command->info('   - Product data published to Redis for order service');
        $this->command->info('   - Restaurant data available for user service integration');
        $this->command->info('   - Basket data ready for payment service processing');
        $this->command->info('   - Real-time events published for notification service');
    }
}
