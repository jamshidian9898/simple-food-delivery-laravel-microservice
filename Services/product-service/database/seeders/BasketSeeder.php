<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BasketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();

        if ($users->isEmpty()) {
            $users = \App\Models\User::factory(5)->create();
        }

        $restaurants = \App\Models\Restaurant::all();

        if ($restaurants->isEmpty()) {
            $restaurants = \App\Models\Restaurant::factory(5)->create();
        }

        // Create some baskets for random users and restaurants
        for ($i = 0; $i < 20; $i++) {
            \App\Models\Basket::factory()->create([
                'user_id' => $users->random()->id,
                'restaurant_id' => $restaurants->random()->id,
            ]);
        }
    }
}
