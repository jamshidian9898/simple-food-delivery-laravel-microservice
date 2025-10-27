<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    public function getProductListPaginated(int $restaurantId, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $list = Product::restaurant($restaurantId)
            ->active()
            ->paginate($limit);

        return $list;
    }

    public function getProductById(int $restaurantId): Product|null
    {
        return Product::restaurant($restaurantId)->first();
    }
}
