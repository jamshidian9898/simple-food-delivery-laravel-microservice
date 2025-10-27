<?php

namespace App\Http\Controllers;

use App\Http\Requests\Basket\AddBasketItemRequest;
use App\Http\Requests\Basket\DeleteBasketItemRequest;
use App\Http\Resources\Basket\BasketItemListResource;
use App\Http\Resources\Basket\BasketItemResource;
use App\Services\BasketService;
use App\Support\RequestContext;

class BasketController extends Controller
{
    public function __construct(
        private BasketService $basketService
    ) {}

    public function show()
    {
        try {
            $basketItems = $this->basketService->getBasketItemsByUserId(
                auth()->id(),
            );

            return response()->json([
                'status' => true,
                'data' => [
                    'basket' => BasketItemListResource::collection($basketItems)
                ],
            ]);
        } catch (\Throwable $th) {
            // TODO: use custome log serive to store error log in log-service
            // user_id => auth()->id(), request_id => RequestContext::getRequestId

            return response()->json([
                'status' => false,
                'message' => 'In get Basket Items we have error. please try again.',
                'data' => [
                    'request_id' => RequestContext::getRequestId()
                ]
            ], 400);
        }
    }

    public function addItem(AddBasketItemRequest $request)
    {
        try {
            $basketItems = $this->basketService->AddItemToUserBasket(
                auth()->id(),
                $request->getProductId(),
                $request->getQuantity(1),
                $request->getNote(),
            );

            return response()->json([
                'status' => true,
                'data' => [
                    'item' => new BasketItemResource($basketItems)
                ],
            ]);
        } catch (\Throwable $th) {
            // TODO: use custome log serive to store error log in log-service
            // user_id => auth()->id(), request_id => RequestContext::getRequestId

            return response()->json([
                'status' => false,
                'message' => 'In add product to your we have error. please try again.',
                'data' => [
                    'request_id' => RequestContext::getRequestId()
                ]
            ], 400);
        }
    }

    public function deleteItem(DeleteBasketItemRequest $request)
    {
        try {
            $basketItems = $this->basketService->deleteItemById(
                $request->getItemId()
            );

            return response()->json([
                'status' => true,
                'data' => [
                    'basket' => new BasketItemResource($basketItems)
                ],
            ]);
        } catch (\Throwable $th) {
            // TODO: use custome log serive to store error log in log-service
            // user_id => auth()->id(), request_id => RequestContext::getRequestId

            return response()->json([
                'status' => false,
                'message' => 'In get Basket Items we have error. please try again.',
                'data' => [
                    'request_id' => RequestContext::getRequestId()
                ]
            ], 400);
        }
    }
}
