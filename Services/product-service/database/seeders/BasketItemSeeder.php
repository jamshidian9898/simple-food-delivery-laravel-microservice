<?php

namespace Database\Seeders;

use App\Infrastructure\Services\ProductDataPublisher;
use App\Models\Basket;
use App\Models\BasketItem;
use App\Models\Product;
use App\Enums\Basket\BasketStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BasketItemSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed basket item data with test items and random ones.
     */
    public function run(): void
    {
        $publisher = new ProductDataPublisher();
        $createdBasketItems = collect();
        
        $this->command->info('🛍️ Starting Basket Item Seeding...');

        // Get all baskets
        $baskets = Basket::all();
        
        if ($baskets->isEmpty()) {
            $this->command->warn('No baskets found. Please run BasketSeeder first.');
            return;
        }

        // Create specific test basket items for active baskets
        $activeBaskets = $baskets->where('status', BasketStatus::active);
        $testItemsCount = 0;

        foreach ($activeBaskets->take(3) as $basket) {
            // Get products from the same restaurant as the basket
            $products = Product::where('restaurant_id', $basket->restaurant_id)
                ->where('is_available', true)
                ->where('quantity', '>', 0)
                ->inRandomOrder()
                ->take(rand(2, 4))
                ->get();

            if ($products->isEmpty()) {
                continue;
            }

            foreach ($products as $product) {
                $quantity = rand(1, 3);
                $unitPrice = $product->price;
                $totalPrice = $unitPrice * $quantity;
                
                $basketItem = BasketItem::firstOrCreate(
                    [
                        'basket_id' => $basket->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'note' => $this->getTestNote($product->name, $quantity),
                    ]
                );
                
                $createdBasketItems->push($basketItem);
                $publisher->publishBasketItem($basketItem);
                $testItemsCount++;
            }
        }

        // Create random basket items for remaining baskets
        $remainingBaskets = $baskets->skip(3);
        $randomItemsCount = 0;

        foreach ($remainingBaskets as $basket) {
            // Skip expired baskets or create fewer items for ordered baskets
            if ($basket->status === BasketStatus::expired) {
                continue;
            }
            
            $maxItems = $basket->status === BasketStatus::ordered ? 2 : rand(1, 5);
            
            $products = Product::where('restaurant_id', $basket->restaurant_id)
                ->inRandomOrder()
                ->take($maxItems)
                ->get();

            foreach ($products as $product) {
                $quantity = rand(1, 4);
                $unitPrice = $product->price;
                $totalPrice = $unitPrice * $quantity;
                
                $basketItem = BasketItem::create([
                    'basket_id' => $basket->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'note' => fake()->optional(0.3)->sentence(),
                ]);
                
                $createdBasketItems->push($basketItem);
                $randomItemsCount++;
            }
        }

        // Publish random basket items in batches
        if ($randomItemsCount > 0) {
            $randomItems = $createdBasketItems->skip($testItemsCount);
            $publisher->publishBasketItems($randomItems);
        }

        $totalItems = $testItemsCount + $randomItemsCount;

        $this->command->info("✅ Ensured {$totalItems} basket items ({$testItemsCount} test + {$randomItemsCount} random)");
        $this->command->info('   Test Items:');
        $this->command->info('   - Items for active baskets with realistic quantities');
        $this->command->info('   - Items with custom notes and proper pricing');
        $this->command->info('   - Skipped expired baskets for realism');
        $this->command->info('📡 Published ' . $createdBasketItems->count() . ' basket items to Redis for cross-service access');
    }

    /**
     * Generate realistic test notes for basket items.
     */
    private function getTestNote(string $productName, int $quantity): ?string
    {
        $notes = [
            'Extra spicy please',
            'No onions',
            'Well done',
            'On the side',
            'Extra sauce',
            'Light on salt',
            'Make it crispy',
            'Add extra cheese',
            null, // Some items have no notes
            null,
        ];

        // Special notes for specific products
        if (str_contains(strtolower($productName), 'pizza')) {
            $pizzaNotes = ['Thin crust', 'Extra cheese', 'Well done', 'Cut into squares', null];
            return fake()->randomElement($pizzaNotes);
        }
        
        if (str_contains(strtolower($productName), 'burger')) {
            $burgerNotes = ['No pickles', 'Extra sauce', 'Medium rare', 'Add bacon', null];
            return fake()->randomElement($burgerNotes);
        }
        
        if (str_contains(strtolower($productName), 'sushi') || str_contains(strtolower($productName), 'sashimi')) {
            $sushiNotes = ['Extra wasabi', 'No ginger', 'Fresh fish only', null, null];
            return fake()->randomElement($sushiNotes);
        }

        return fake()->randomElement($notes);
    }
}
