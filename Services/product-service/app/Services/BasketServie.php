<?php

namespace App\Services;

use App\Models\BasketItem;
use App\Repositories\Contracts\BasketRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BasketService
{
    public function __construct(
        private BasketRepositoryInterface $basketRepo,
        private ProductRepositoryInterface $productRepo,
        private ProductPriceCaculatorService $priceCaculator
    ) {}

    public function getBasketItemsByUserId(string $userId): Collection
    {
        $list = $this->basketRepo->getBasketItemsByUserId($userId);

        return $list;
    }

    public function AddItemToUserBasket(string $userId, int $productId, int $quantity = 1, string $note = ''): BasketItem
    {
        // Check if the basket exists for the user, if not create one
        $basket = $this->basketRepo->getOrCreateBasketByUserId($userId);

        // if item alerady exists in the basket, update the quantity
        $existingItem = $this->basketRepo->getItemByProductIdAndBasketId(
            $productId,
            $basket->id
        );

        // Update basket item quantity if already exists
        if ($existingItem) {
            $item = $this->basketRepo->updateBasketItemQueantity(
                $basket->id,
                $productId,
                $quantity
            );

            return $item;
        }

        // get product record
        $product = $this->productRepo->getProductById($productId);

        // Calculate product price
        $totalPrice = $this->priceCaculator->calculateTotal($product->price, $quantity);

        // Add product to basket item list
        $item = $this->basketRepo->AddItemToUserBasket(
            $userId,
            $basket->id,
            $productId,
            $quantity,
            $product->price,
            $totalPrice,
            $note
        );

        return $item;
    }

    public function deleteItemById(int $basketItemId): bool
    {
        $result = $this->basketRepo->delteItem($basketItemId);

        return $result;
    }
}
