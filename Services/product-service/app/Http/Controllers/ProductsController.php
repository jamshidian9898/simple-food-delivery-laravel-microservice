<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\ProductListRequest;
use App\Http\Requests\Product\ProductShowRequest;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\Product\ProductResource;
use App\Services\ProductServie;
use App\Support\RequestContext;

class ProductsController extends Controller
{
    public function __construct(
        private ProductServie $productServie
    ) {}

    public function index(ProductListRequest $request)
    {
        try {
            $productList = $this->productServie->getProductListByRestaurantPaginated(
                $request->getRestaurantId(),
                $request->getPage(1),
                $request->getLimit(10)
            );

            return response()->json([
                'status' => true,
                'data' => [
                    'products' => ProductListResource::collection($productList)
                ],
            ]);
        } catch (\Throwable $th) {
            // TODO: use custome log serive to store error log in log-service
            // user_id => auth()->id(), request_id => RequestContext::getRequestId

            return response()->json([
                'status' => false,
                'message' => 'in get Product list we have error. please try again.',
                'data' => [
                    'request_id' => RequestContext::getRequestId()
                ]
            ], 400);
        }
    }

    public function show(ProductShowRequest $request)
    {
        try {
            $restaurantList = $this->productServie->getProductDetailes(
                $request->getProductId(),
            );

            return response()->json([
                'status' => true,
                'data' => [
                    'product' => new ProductResource($restaurantList)
                ],
            ]);
        } catch (\Throwable $th) {
            // TODO: use custome log serive to store error log in log-service
            // user_id => auth()->id(), request_id => RequestContext::getRequestId

            return response()->json([
                'status' => false,
                'message' => 'in get product we have error. please try again.',
                'data' => [
                    'request_id' => RequestContext::getRequestId()
                ]
            ], 400);
        }
    }
}
