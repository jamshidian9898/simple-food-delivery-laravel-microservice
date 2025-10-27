<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // For each restaurant create several products
        \App\Models\Restaurant::all()->each(function (\App\Models\Restaurant $restaurant) {
            \App\Models\Product::factory()
                ->count(8)
                ->create(['restaurant_id' => $restaurant->id]);
        });
    }
}
