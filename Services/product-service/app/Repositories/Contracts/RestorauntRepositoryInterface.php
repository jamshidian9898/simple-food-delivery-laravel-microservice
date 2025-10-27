<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface RestorauntRepositoryInterface
{
    public function getRestaurantListPaginated(int $page = 1, int $limit = 10): LengthAwarePaginator;
}
