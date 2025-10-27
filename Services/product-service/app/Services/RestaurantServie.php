<?php

namespace App\Services;

use App\Repositories\Contracts\RestorauntRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class RestaurantServie
{
    public function __construct(
        private RestorauntRepositoryInterface $restorauntRepo
    ) {}

    public function getRestaurantListPaginated($page = 1, $limit = 10): LengthAwarePaginator
    {
        $restaurantList = $this->restorauntRepo->getRestaurantListPaginated($page, $limit);

        return $restaurantList;
    }
}
