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
     * Register additional service container bindings or other registration logic.
     *
     * Use this method to add custom bindings or perform registration steps beyond
     * the automatic bindings declared in the `$bindings` property.
     */
    public function register(): void
    {
        // Bindings are automatically registered via the $bindings property
        // But we can also add custom registration logic here if needed
    }

    /**
     * Perform provider bootstrapping after all services have been registered.
     *
     * Place provider-specific initialization (for example event listeners, route or
     * model bindings, and publishing of resources). Currently no boot logic is defined.
     */
    public function boot(): void
    {
        // Any bootstrapping logic can go here
    }
}
