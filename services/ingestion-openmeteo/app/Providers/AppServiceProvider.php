<?php

namespace App\Providers;

use App\Application\Outbox\Contracts\OutboxRepository;
use App\Application\Weather\Contracts\LocationRepository;
use App\Application\Weather\Contracts\WeatherDataSource;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentLocationRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentOutboxRepository;
use App\Infrastructure\Sources\OpenMeteo\OpenMeteoWeatherDataSource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LocationRepository::class, EloquentLocationRepository::class);
        $this->app->bind(OutboxRepository::class, EloquentOutboxRepository::class);
        $this->app->bind(WeatherDataSource::class, OpenMeteoWeatherDataSource::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
