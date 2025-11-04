<?php

namespace App\Repositories;

use App\Models\Restaurant;
use App\Repositories\Contracts\RestaurantRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class RestaurantRepository implements RestaurantRepositoryInterface
{
    public function getRestaurantListPaginated(int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $list = Restaurant::active()->paginate($limit);

        return $list;
    }
}
