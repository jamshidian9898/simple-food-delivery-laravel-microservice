<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function getProductListPaginated(int $restaurantId, int $page = 1, int $limit = 10): LengthAwarePaginator;
    public function getProductById(int $productId): Product|null;
}
