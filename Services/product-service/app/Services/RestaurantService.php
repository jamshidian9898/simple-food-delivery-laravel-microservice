<?php

namespace App\Services;

use App\Repositories\Contracts\RestaurantRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class RestaurantService
{
    /**
     * Create a new RestaurantService instance with the provided restaurant repository.
     */
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepo
    ) {}

    /**
     * Retrieve a paginated list of restaurants.
     *
     * @param int $page Page number, starting at 1.
     * @param int $limit Number of restaurants per page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator The paginated list of restaurants.
     */
    public function getRestaurantListPaginated($page = 1, $limit = 10): LengthAwarePaginator
    {
        $restaurantList = $this->restaurantRepo->getRestaurantListPaginated($page, $limit);

        return $restaurantList;
    }
}
