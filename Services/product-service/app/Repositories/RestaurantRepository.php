<?php

namespace App\Repositories;

use App\Models\Restaurant;
use App\Repositories\Contracts\RestaurantRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class RestaurantRepository implements RestaurantRepositoryInterface
{
    /**
     * Retrieve a paginated list of active restaurants.
     *
     * The `$page` parameter is accepted for compatibility but is ignored; the paginator determines the current page from the request. `$limit` sets the number of items per page.
     *
     * @param int $page Accepted for compatibility but ignored; current page is determined by the paginator/request.
     * @param int $limit Number of restaurants per page.
     * @return \Illuminate\Pagination\LengthAwarePaginator The paginated collection of active restaurants.
     */
    public function getRestaurantListPaginated(int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $list = Restaurant::active()->paginate($limit);

        return $list;
    }
}
