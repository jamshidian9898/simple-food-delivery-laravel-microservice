<?php

namespace App\Infrastructure\Services;

use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Basket;
use App\Models\BasketItem;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ProductDataPublisher
{
    private const PRODUCT_DATA_PREFIX = 'seed:products:';
    private const RESTAURANT_DATA_PREFIX = 'seed:restaurants:';
    private const BASKET_DATA_PREFIX = 'seed:baskets:';
    private const BASKET_ITEM_DATA_PREFIX = 'seed:basket_items:';
    
    private const PRODUCT_LIST_PREFIX = 'seed:products:list:';
    private const RESTAURANT_LIST_PREFIX = 'seed:restaurants:list:';
    private const BASKET_LIST_PREFIX = 'seed:baskets:list:';
    
    private const ALL_PRODUCTS_KEY = 'seed:products:all';
    private const ALL_RESTAURANTS_KEY = 'seed:restaurants:all';
    private const ALL_BASKETS_KEY = 'seed:baskets:all';
    private const ALL_BASKET_ITEMS_KEY = 'seed:basket_items:all';

    // Restaurant methods
    public function publishRestaurant(Restaurant $restaurant): void
    {
        try {
            $restaurantData = $this->prepareRestaurantData($restaurant);
            
            $restaurantKey = self::RESTAURANT_DATA_PREFIX . $restaurant->id;
            Redis::setex($restaurantKey, 3600, json_encode($restaurantData));
            
            $statusListKey = self::RESTAURANT_LIST_PREFIX . $restaurant->status->value;
            Redis::sadd($statusListKey, $restaurant->id);
            Redis::expire($statusListKey, 3600);
            
            Redis::sadd(self::ALL_RESTAURANTS_KEY, $restaurant->id);
            Redis::expire(self::ALL_RESTAURANTS_KEY, 3600);
            
            Redis::publish('restaurant.created', json_encode([
                'event' => 'restaurant.created',
                'restaurant_id' => $restaurant->id,
                'status' => $restaurant->status->value,
                'timestamp' => now()->toISOString(),
                'data' => $restaurantData
            ]));
            
        } catch (\Exception $e) {
            Log::error("Failed to publish restaurant data to Redis", [
                'restaurant_id' => $restaurant->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function publishRestaurants(\Illuminate\Support\Collection $restaurants): void
    {
        foreach ($restaurants as $restaurant) {
            $this->publishRestaurant($restaurant);
        }
        
        Log::info("Published batch of restaurants to Redis", [
            'count' => $restaurants->count(),
            'statuses' => $restaurants->groupBy('status')->map->count()->toArray()
        ]);
    }

    // Product methods
    public function publishProduct(Product $product): void
    {
        try {
            $productData = $this->prepareProductData($product);
            
            $productKey = self::PRODUCT_DATA_PREFIX . $product->id;
            Redis::setex($productKey, 3600, json_encode($productData));
            
            $restaurantListKey = self::PRODUCT_LIST_PREFIX . 'restaurant:' . $product->restaurant_id;
            Redis::sadd($restaurantListKey, $product->id);
            Redis::expire($restaurantListKey, 3600);
            
            $availabilityListKey = self::PRODUCT_LIST_PREFIX . ($product->is_available ? 'available' : 'unavailable');
            Redis::sadd($availabilityListKey, $product->id);
            Redis::expire($availabilityListKey, 3600);
            
            Redis::sadd(self::ALL_PRODUCTS_KEY, $product->id);
            Redis::expire(self::ALL_PRODUCTS_KEY, 3600);
            
            Redis::publish('product.created', json_encode([
                'event' => 'product.created',
                'product_id' => $product->id,
                'restaurant_id' => $product->restaurant_id,
                'is_available' => $product->is_available,
                'timestamp' => now()->toISOString(),
                'data' => $productData
            ]));
            
        } catch (\Exception $e) {
            Log::error("Failed to publish product data to Redis", [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function publishProducts(\Illuminate\Support\Collection $products): void
    {
        foreach ($products as $product) {
            $this->publishProduct($product);
        }
        
        Log::info("Published batch of products to Redis", [
            'count' => $products->count(),
            'available' => $products->where('is_available', true)->count(),
            'unavailable' => $products->where('is_available', false)->count()
        ]);
    }

    // Basket methods
    public function publishBasket(Basket $basket): void
    {
        try {
            $basketData = $this->prepareBasketData($basket);
            
            $basketKey = self::BASKET_DATA_PREFIX . $basket->id;
            Redis::setex($basketKey, 3600, json_encode($basketData));
            
            $statusListKey = self::BASKET_LIST_PREFIX . $basket->status->value;
            Redis::sadd($statusListKey, $basket->id);
            Redis::expire($statusListKey, 3600);
            
            $userListKey = self::BASKET_LIST_PREFIX . 'user:' . $basket->user_id;
            Redis::sadd($userListKey, $basket->id);
            Redis::expire($userListKey, 3600);
            
            Redis::sadd(self::ALL_BASKETS_KEY, $basket->id);
            Redis::expire(self::ALL_BASKETS_KEY, 3600);
            
            Redis::publish('basket.created', json_encode([
                'event' => 'basket.created',
                'basket_id' => $basket->id,
                'user_id' => $basket->user_id,
                'restaurant_id' => $basket->restaurant_id,
                'status' => $basket->status->value,
                'timestamp' => now()->toISOString(),
                'data' => $basketData
            ]));
            
        } catch (\Exception $e) {
            Log::error("Failed to publish basket data to Redis", [
                'basket_id' => $basket->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function publishBaskets(\Illuminate\Support\Collection $baskets): void
    {
        foreach ($baskets as $basket) {
            $this->publishBasket($basket);
        }
        
        Log::info("Published batch of baskets to Redis", [
            'count' => $baskets->count(),
            'statuses' => $baskets->groupBy('status')->map->count()->toArray()
        ]);
    }

    // Basket Item methods
    public function publishBasketItem(BasketItem $basketItem): void
    {
        try {
            $basketItemData = $this->prepareBasketItemData($basketItem);
            
            $basketItemKey = self::BASKET_ITEM_DATA_PREFIX . $basketItem->id;
            Redis::setex($basketItemKey, 3600, json_encode($basketItemData));
            
            Redis::sadd(self::ALL_BASKET_ITEMS_KEY, $basketItem->id);
            Redis::expire(self::ALL_BASKET_ITEMS_KEY, 3600);
            
            Redis::publish('basket_item.created', json_encode([
                'event' => 'basket_item.created',
                'basket_item_id' => $basketItem->id,
                'basket_id' => $basketItem->basket_id,
                'product_id' => $basketItem->product_id,
                'timestamp' => now()->toISOString(),
                'data' => $basketItemData
            ]));
            
        } catch (\Exception $e) {
            Log::error("Failed to publish basket item data to Redis", [
                'basket_item_id' => $basketItem->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function publishBasketItems(\Illuminate\Support\Collection $basketItems): void
    {
        foreach ($basketItems as $basketItem) {
            $this->publishBasketItem($basketItem);
        }
        
        Log::info("Published batch of basket items to Redis", [
            'count' => $basketItems->count()
        ]);
    }

    // Data preparation methods
    private function prepareRestaurantData(Restaurant $restaurant): array
    {
        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'description' => $restaurant->description,
            'address' => $restaurant->address,
            'status' => $restaurant->status->value,
            'status_label' => $restaurant->status->label(),
            'created_at' => $restaurant->created_at->toISOString(),
            'updated_at' => $restaurant->updated_at->toISOString(),
            'is_active' => $restaurant->status->value === 'active',
        ];
    }
    
    private function prepareProductData(Product $product): array
    {
        return [
            'id' => $product->id,
            'restaurant_id' => $product->restaurant_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price,
            'price_formatted' => number_format($product->price / 100, 2),
            'image_url' => $product->image_url,
            'is_available' => $product->is_available,
            'quantity' => $product->quantity,
            'estimated_preparation_time' => $product->estimated_preparation_time,
            'created_at' => $product->created_at->toISOString(),
            'updated_at' => $product->updated_at->toISOString(),
            'in_stock' => $product->quantity > 0,
        ];
    }
    
    private function prepareBasketData(Basket $basket): array
    {
        return [
            'id' => $basket->id,
            'user_id' => $basket->user_id,
            'restaurant_id' => $basket->restaurant_id,
            'status' => $basket->status->value,
            'status_label' => $basket->status->label(),
            'created_at' => $basket->created_at->toISOString(),
            'updated_at' => $basket->updated_at->toISOString(),
            'is_active' => $basket->status->value === 'active',
        ];
    }
    
    private function prepareBasketItemData(BasketItem $basketItem): array
    {
        return [
            'id' => $basketItem->id,
            'basket_id' => $basketItem->basket_id,
            'product_id' => $basketItem->product_id,
            'quantity' => $basketItem->quantity,
            'unit_price' => $basketItem->unit_price,
            'unit_price_formatted' => number_format($basketItem->unit_price / 100, 2),
            'total_price' => $basketItem->total_price,
            'total_price_formatted' => number_format($basketItem->total_price / 100, 2),
            'note' => $basketItem->note,
            'created_at' => $basketItem->created_at->toISOString(),
            'updated_at' => $basketItem->updated_at->toISOString(),
        ];
    }

    // Cleanup methods
    public function clearSeedData(): void
    {
        try {
            // Clear restaurants
            $restaurantIds = Redis::smembers(self::ALL_RESTAURANTS_KEY);
            foreach ($restaurantIds as $restaurantId) {
                Redis::del(self::RESTAURANT_DATA_PREFIX . $restaurantId);
            }
            Redis::del(self::RESTAURANT_LIST_PREFIX . 'active');
            Redis::del(self::RESTAURANT_LIST_PREFIX . 'deactive');
            Redis::del(self::ALL_RESTAURANTS_KEY);
            
            // Clear products
            $productIds = Redis::smembers(self::ALL_PRODUCTS_KEY);
            foreach ($productIds as $productId) {
                Redis::del(self::PRODUCT_DATA_PREFIX . $productId);
            }
            Redis::del(self::PRODUCT_LIST_PREFIX . 'available');
            Redis::del(self::PRODUCT_LIST_PREFIX . 'unavailable');
            Redis::del(self::ALL_PRODUCTS_KEY);
            
            // Clear baskets
            $basketIds = Redis::smembers(self::ALL_BASKETS_KEY);
            foreach ($basketIds as $basketId) {
                Redis::del(self::BASKET_DATA_PREFIX . $basketId);
            }
            Redis::del(self::BASKET_LIST_PREFIX . 'active');
            Redis::del(self::BASKET_LIST_PREFIX . 'ordered');
            Redis::del(self::BASKET_LIST_PREFIX . 'expired');
            Redis::del(self::ALL_BASKETS_KEY);
            
            // Clear basket items
            $basketItemIds = Redis::smembers(self::ALL_BASKET_ITEMS_KEY);
            foreach ($basketItemIds as $basketItemId) {
                Redis::del(self::BASKET_ITEM_DATA_PREFIX . $basketItemId);
            }
            Redis::del(self::ALL_BASKET_ITEMS_KEY);
            
            Log::info("Cleared all seeded product service data from Redis", [
                'cleared_restaurants' => count($restaurantIds),
                'cleared_products' => count($productIds),
                'cleared_baskets' => count($basketIds),
                'cleared_basket_items' => count($basketItemIds)
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to clear seeded product service data from Redis", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getStatistics(): array
    {
        try {
            return [
                'total_restaurants' => Redis::scard(self::ALL_RESTAURANTS_KEY),
                'active_restaurants' => Redis::scard(self::RESTAURANT_LIST_PREFIX . 'active'),
                'deactive_restaurants' => Redis::scard(self::RESTAURANT_LIST_PREFIX . 'deactive'),
                'total_products' => Redis::scard(self::ALL_PRODUCTS_KEY),
                'available_products' => Redis::scard(self::PRODUCT_LIST_PREFIX . 'available'),
                'unavailable_products' => Redis::scard(self::PRODUCT_LIST_PREFIX . 'unavailable'),
                'total_baskets' => Redis::scard(self::ALL_BASKETS_KEY),
                'active_baskets' => Redis::scard(self::BASKET_LIST_PREFIX . 'active'),
                'ordered_baskets' => Redis::scard(self::BASKET_LIST_PREFIX . 'ordered'),
                'expired_baskets' => Redis::scard(self::BASKET_LIST_PREFIX . 'expired'),
                'total_basket_items' => Redis::scard(self::ALL_BASKET_ITEMS_KEY),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get product service statistics from Redis", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
