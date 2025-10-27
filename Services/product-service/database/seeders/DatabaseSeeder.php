<?php

namespace Database\Seeders;

use App\Models\User;
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

        // Seed restaurants and products
        $this->call([
            \Database\Seeders\RestaurantSeeder::class,
            \Database\Seeders\ProductSeeder::class,
        ]);

        // Seed baskets and items
        $this->call([
            \Database\Seeders\BasketSeeder::class,
            \Database\Seeders\BasketItemSeeder::class,
        ]);
    }
}
