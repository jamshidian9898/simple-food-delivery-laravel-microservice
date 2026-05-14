<?php

namespace App\Http\Controllers;

use App\Http\Requests\Restaurant\RestaurantListRequest;
use App\Http\Resources\Restaurant\RestaurantListResource;
use App\Services\RestaurantService;
use App\Support\RequestContext;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    /**
     * Create a new controller instance with the injected RestaurantService.
     *
     * @param RestaurantService $restaurantService Service used to retrieve and manage restaurant data.
     */
    public function __construct(
        private RestaurantService $restaurantService
    ) {}

    /**
     * Return a paginated list of restaurants as a JSON response or an error payload with a request id.
     *
     * @param \App\Http\Requests\RestaurantListRequest $request The validated request providing pagination inputs; `getPage(1)` and `getLimit(10)` supply defaults when absent.
     * @return \Illuminate\Http\JsonResponse On success: JSON with `status: true` and `data.restaurants` containing a `RestaurantListResource` collection. On failure: JSON with `status: false`, a `message` string, and `data.request_id`; the failure response is returned with HTTP status 400.
     */
    public function index(RestaurantListRequest $request)
    {
        try {
            $restaurantList = $this->restaurantService->getRestaurantListPaginated(
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
