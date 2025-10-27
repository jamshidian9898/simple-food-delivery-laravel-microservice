<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BasketItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Basket::all()->each(function (\App\Models\Basket $basket) {
            $products = \App\Models\Product::where('restaurant_id', $basket->restaurant_id)
                ->inRandomOrder()
                ->take(fake()->numberBetween(1, 5))
                ->get();

            foreach ($products as $product) {
                $quantity = fake()->numberBetween(1, 4);
                \App\Models\BasketItem::create([
                    'basket_id' => $basket->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $quantity,
                    'note' => fake()->optional()->sentence(),
                ]);
            }
        });
    }
}
