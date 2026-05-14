<?php

namespace Database\Seeders;

use App\Infrastructure\Services\UserDataPublisher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting User Service Database Seeding...');
        $this->command->info('');

        // Seed in logical order
        $this->call([
            AdminSeeder::class,      // Admin users first
            CustomerSeeder::class,   // Customer users
            RestaurantSeeder::class, // Restaurant users  
            CourierSeeder::class,    // Courier users
        ]);

        $this->command->info('');
        $this->command->info('🎉 User Service Database Seeding Completed!');
        $this->command->info('📊 Total Users Ensured:');
        $this->command->info('   - 3 Admin users');
        $this->command->info('   - 50 Customer users');
        $this->command->info('   - 20 Restaurant users');
        $this->command->info('   - 15 Courier users');
        $this->command->info('   - Total: 88 users');

        // Show Redis statistics
        $publisher = new UserDataPublisher();
        $stats = $publisher->getStatistics();
        $this->command->info('');
        $this->command->info('📡 Redis Cross-Service Data:');
        $this->command->info('   - Total users in Redis: ' . ($stats['total_users'] ?? 0));
        $this->command->info('   - Customers: ' . ($stats['customers'] ?? 0));
        $this->command->info('   - Restaurants: ' . ($stats['restaurants'] ?? 0));
        $this->command->info('   - Couriers: ' . ($stats['couriers'] ?? 0));
    }
}
