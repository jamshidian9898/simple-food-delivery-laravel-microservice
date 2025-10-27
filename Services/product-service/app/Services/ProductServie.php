<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductServie
{
    public function __construct(
        private ProductRepositoryInterface $productRepo
    ) {}

    public function getProductListByRestaurantPaginated(int $restaurantId, $page = 1, $limit = 10): LengthAwarePaginator
    {
        $list = $this->productRepo->getProductListPaginated($restaurantId, $page, $limit);

        return $list;
    }
   
    public function getProductDetailes(int $id): Product
    {
        $product = $this->productRepo->getProductById($id);

        return $product;
    }
}
