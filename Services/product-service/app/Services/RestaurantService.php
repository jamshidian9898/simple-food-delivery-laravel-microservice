<?php

namespace App\Services;

use App\Repositories\Contracts\RestaurantRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class RestaurantService
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepo
    ) {}

    public function getRestaurantListPaginated($page = 1, $limit = 10): LengthAwarePaginator
    {
        $restaurantList = $this->restaurantRepo->getRestaurantListPaginated($page, $limit);

        return $restaurantList;
    }
}
