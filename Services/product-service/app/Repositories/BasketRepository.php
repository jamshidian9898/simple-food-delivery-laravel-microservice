<?php

namespace App\Repositories;

use App\Models\Basket;
use App\Models\BasketItem;
use App\Repositories\Contracts\BasketRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BasketRepository implements BasketRepositoryInterface
{
    public function getBasketItemsByUserId(string $userId): Collection
    {
        $basket = Basket::ByUserId($userId)->first();

        $items = BasketItem::byBasket($basket->id)->get();

        return $items;
    }

    public function getOrCreateBasketByUserId(string $userId): Basket
    {
        $basket = Basket::byUser($userId)->firstOrCreate(['user_id' => $userId]);

        return $basket;
    }

    public function getItemByProductIdAndBasketId(int $productId, int $basketId): BasketItem
    {
        $item = BasketItem::byProduct($productId)->byBasket($basketId)->first();

        return $item;
    }

    public function updateBasketItemQueantity(int $basketId, string $productId, int $quantity): BasketItem
    {
        $item = BasketItem::byProduct($productId)
            ->byBasket($basketId)
            ->update(['quantity' => $quantity]);

        return $item;
    }

    public function AddItemToUserBasket(string $userId, int $basketId, int $productId, int $quantity, int $unitPrice, int $totalPrice, string $note): BasketItem
    {
        $item = BasketItem::create([
            'basket_id' => $basketId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'notes' => $note,
        ]);

        return $item;
    }

    public function delteItem(int $itemId): bool
    {
        $result = BasketItem::byId($itemId)->delete();

        return $result;
    }
}
