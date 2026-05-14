<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface RestaurantRepositoryInterface
{
    /**
 * Retrieve a paginated list of restaurants.
 *
 * @param int $page The 1-based page number to retrieve (default 1).
 * @param int $limit The number of restaurants per page (default 10).
 * @return \Illuminate\Pagination\LengthAwarePaginator A paginator containing restaurant records for the requested page.
 */
public function getRestaurantListPaginated(int $page = 1, int $limit = 10): LengthAwarePaginator;
}
