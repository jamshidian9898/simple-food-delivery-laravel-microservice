<?php

namespace App\Http\Controllers;

use App\Http\Requests\Restaurant\RestaurantListRequest;
use App\Http\Resources\Restaurant\RestaurantListResource;
use App\Services\RestaurantServie;
use App\Support\RequestContext;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function __construct(
        private RestaurantServie $restaurantServie
    ) {}

    public function index(RestaurantListRequest $request)
    {
        try {
            $restaurantList = $this->restaurantServie->getRestaurantListPaginated(
                $request->getPage(1),
                $request->getLimit(10)
            );

            return response()->json([
                'status' => true,
                'data' => [
                    'restaurants' => RestaurantListResource::collection($restaurantList)
                ],
            ]);
        } catch (\Throwable $th) {
            // TODO: use custome log serive to store error log in log-service
            // user_id => auth()->id(), request_id => RequestContext::getRequestId

            return response()->json([
                'status' => false,
                'message' => 'in get Restaurant list we have error. please try again.',
                'data' => [
                    'request_id' => RequestContext::getRequestId()
                ]
            ], 400);
        }
    }
}
