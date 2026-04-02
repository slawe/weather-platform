<?php

namespace App\Providers;

use App\Application\Messaging\Contracts\IdempotencyRepository;
use App\Application\Weather\Contracts\WeatherSnapshotRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentIdempotencyRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentWeatherSnapshotRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IdempotencyRepository::class, EloquentIdempotencyRepository::class);
        $this->app->bind(WeatherSnapshotRepository::class, EloquentWeatherSnapshotRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
