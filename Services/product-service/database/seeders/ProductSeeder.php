<?php

namespace Database\Seeders;

use App\Infrastructure\Services\ProductDataPublisher;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed product data with known test products and random ones.
     */
    public function run(): void
    {
        $publisher = new ProductDataPublisher();
        $createdProducts = collect();
        
        $this->command->info('🍕 Starting Product Seeding...');

        // Get test restaurants for specific products
        $pizzaPalace = Restaurant::where('name', 'Pizza Palace')->first();
        $burgerKingdom = Restaurant::where('name', 'Burger Kingdom')->first();
        $sushiMaster = Restaurant::where('name', 'Sushi Master')->first();
        $tacoFiesta = Restaurant::where('name', 'Taco Fiesta')->first();
        $indianSpice = Restaurant::where('name', 'Indian Spice House')->first();

        // Create test products for specific restaurants
        $testProducts = [];
        
        if ($pizzaPalace) {
            $testProducts = array_merge($testProducts, [
                [
                    'restaurant_id' => $pizzaPalace->id,
                    'name' => 'Margherita Pizza',
                    'slug' => 'margherita-pizza',
                    'description' => 'Classic Italian pizza with fresh mozzarella, tomatoes, and basil.',
                    'price' => 1299, // $12.99
                    'image_url' => 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 50,
                    'estimated_preparation_time' => 15,
                ],
                [
                    'restaurant_id' => $pizzaPalace->id,
                    'name' => 'Pepperoni Pizza',
                    'slug' => 'pepperoni-pizza',
                    'description' => 'Traditional pepperoni pizza with mozzarella cheese.',
                    'price' => 1499, // $14.99
                    'image_url' => 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 45,
                    'estimated_preparation_time' => 18,
                ],
            ]);
        }

        if ($burgerKingdom) {
            $testProducts = array_merge($testProducts, [
                [
                    'restaurant_id' => $burgerKingdom->id,
                    'name' => 'Classic Cheeseburger',
                    'slug' => 'classic-cheeseburger',
                    'description' => 'Juicy beef patty with cheese, lettuce, tomato, and special sauce.',
                    'price' => 899, // $8.99
                    'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 30,
                    'estimated_preparation_time' => 12,
                ],
                [
                    'restaurant_id' => $burgerKingdom->id,
                    'name' => 'Crispy Chicken Burger',
                    'slug' => 'crispy-chicken-burger',
                    'description' => 'Crispy fried chicken breast with mayo and fresh vegetables.',
                    'price' => 1099, // $10.99
                    'image_url' => 'https://images.unsplash.com/photo-1606755962773-d324e2dabd3f?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 25,
                    'estimated_preparation_time' => 15,
                ],
            ]);
        }

        if ($sushiMaster) {
            $testProducts = array_merge($testProducts, [
                [
                    'restaurant_id' => $sushiMaster->id,
                    'name' => 'Salmon Sashimi',
                    'slug' => 'salmon-sashimi',
                    'description' => 'Fresh Atlantic salmon sashimi, 8 pieces.',
                    'price' => 1899, // $18.99
                    'image_url' => 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 20,
                    'estimated_preparation_time' => 10,
                ],
                [
                    'restaurant_id' => $sushiMaster->id,
                    'name' => 'California Roll',
                    'slug' => 'california-roll',
                    'description' => 'Classic California roll with crab, avocado, and cucumber.',
                    'price' => 1299, // $12.99
                    'image_url' => 'https://images.unsplash.com/photo-1617196034796-73dfa7b1fd56?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 35,
                    'estimated_preparation_time' => 8,
                ],
            ]);
        }

        if ($tacoFiesta) {
            $testProducts = array_merge($testProducts, [
                [
                    'restaurant_id' => $tacoFiesta->id,
                    'name' => 'Beef Tacos',
                    'slug' => 'beef-tacos',
                    'description' => 'Three soft tacos with seasoned ground beef, lettuce, and cheese.',
                    'price' => 799, // $7.99
                    'image_url' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 40,
                    'estimated_preparation_time' => 10,
                ],
                [
                    'restaurant_id' => $tacoFiesta->id,
                    'name' => 'Chicken Burrito',
                    'slug' => 'chicken-burrito',
                    'description' => 'Large burrito with grilled chicken, rice, beans, and salsa.',
                    'price' => 1199, // $11.99
                    'image_url' => 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 30,
                    'estimated_preparation_time' => 12,
                ],
            ]);
        }

        if ($indianSpice) {
            $testProducts = array_merge($testProducts, [
                [
                    'restaurant_id' => $indianSpice->id,
                    'name' => 'Chicken Tikka Masala',
                    'slug' => 'chicken-tikka-masala',
                    'description' => 'Tender chicken in creamy tomato-based curry sauce.',
                    'price' => 1599, // $15.99
                    'image_url' => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 25,
                    'estimated_preparation_time' => 20,
                ],
                [
                    'restaurant_id' => $indianSpice->id,
                    'name' => 'Vegetable Biryani',
                    'slug' => 'vegetable-biryani',
                    'description' => 'Aromatic basmati rice with mixed vegetables and spices.',
                    'price' => 1399, // $13.99
                    'image_url' => 'https://images.unsplash.com/photo-1563379091339-03246963d96c?w=640&h=480',
                    'is_available' => true,
                    'quantity' => 30,
                    'estimated_preparation_time' => 25,
                ],
                [
                    'restaurant_id' => $indianSpice->id,
                    'name' => 'Out of Stock Item',
                    'slug' => 'out-of-stock-item',
                    'description' => 'This item is currently out of stock.',
                    'price' => 999, // $9.99
                    'image_url' => null,
                    'is_available' => false,
                    'quantity' => 0,
                    'estimated_preparation_time' => 15,
                ],
            ]);
        }

        // Create test products
        foreach ($testProducts as $productData) {
            $product = Product::firstOrCreate(
                [
                    'restaurant_id' => $productData['restaurant_id'],
                    'slug' => $productData['slug']
                ],
                $productData
            );
            $createdProducts->push($product);
            $publisher->publishProduct($product);
        }

        // Create random products for all restaurants
        $allRestaurants = Restaurant::all();
        $randomProductsCount = 0;
        
        foreach ($allRestaurants as $restaurant) {
            $productsPerRestaurant = rand(5, 12); // Random number of products per restaurant
            $randomProducts = Product::factory()
                ->count($productsPerRestaurant)
                ->create(['restaurant_id' => $restaurant->id]);
            
            $createdProducts = $createdProducts->merge($randomProducts);
            $publisher->publishProducts($randomProducts);
            $randomProductsCount += $productsPerRestaurant;
        }

        $testProductsCount = count($testProducts);
        $totalProducts = $testProductsCount + $randomProductsCount;

        $this->command->info("✅ Ensured {$totalProducts} products ({$testProductsCount} test + {$randomProductsCount} random)");
        $this->command->info('   Test Products:');
        $this->command->info('   - Margherita Pizza (Pizza Palace)');
        $this->command->info('   - Classic Cheeseburger (Burger Kingdom)');
        $this->command->info('   - Salmon Sashimi (Sushi Master)');
        $this->command->info('   - Beef Tacos (Taco Fiesta)');
        $this->command->info('   - Chicken Tikka Masala (Indian Spice House)');
        $this->command->info('   - Out of Stock Item (unavailable)');
        $this->command->info('📡 Published ' . $createdProducts->count() . ' products to Redis for cross-service access');
    }
}
