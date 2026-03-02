<?php

namespace GPapakitsos\LaravelDatatables;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DatatablesServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/datatables.php', 'datatables');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/datatables.php' => config_path('datatables.php'),
            ]);
        }

        Route::middlewareGroup('datatables', config('datatables.middleware', []));
        Route::group([
            'namespace' => 'GPapakitsos\LaravelDatatables\Http\Controllers',
            'prefix' => config('datatables.routes.prefix'),
            'middleware' => 'datatables',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/routes/datatables.php');
        });
    }
}
