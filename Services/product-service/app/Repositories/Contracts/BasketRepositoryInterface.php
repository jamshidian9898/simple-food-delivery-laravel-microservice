<?php

namespace App\Repositories\Contracts;

use App\Models\Basket;
use App\Models\BasketItem;
use Illuminate\Database\Eloquent\Collection;

interface BasketRepositoryInterface
{
    public function getBasketItemsByUserId(string $userId): Collection;
    public function getOrCreateBasketByUserId(string $userId): Basket;
    public function getItemByProductIdAndBasketId(int $productId, int $basketId): BasketItem;
    public function updateBasketItemQueantity(int $basketId, string $productId, int $quantity): BasketItem;
    public function AddItemToUserBasket(string $userId, int $basketId, int $productId, int $quantity, int $unitPrice, int $totalPrice, string $note): BasketItem;
    public function delteItem(int $itemId): bool;
}
