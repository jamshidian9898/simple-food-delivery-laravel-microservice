<?php

namespace App\Providers;

use App\Repositories\BasketRepository;
use App\Repositories\Contracts\BasketRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\RestaurantRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\RestaurantRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        BasketRepositoryInterface::class => BasketRepository::class,
        ProductRepositoryInterface::class => ProductRepository::class,
        RestaurantRepositoryInterface::class => RestaurantRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Bindings are automatically registered via the $bindings property
        // But we can also add custom registration logic here if needed
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Any bootstrapping logic can go here
    }
}
