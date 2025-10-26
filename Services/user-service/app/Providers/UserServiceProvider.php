<?php

namespace App\Providers;

use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\Services\AuthenticationServiceInterface;
use App\Infrastructure\Repositories\EloquentUserRepository;
use App\Infrastructure\Services\LaravelAuthenticationService;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(AuthenticationServiceInterface::class, LaravelAuthenticationService::class);
    }

    public function boot(): void
    {
        //
    }
}
