<?php

namespace GPapakitsos\LaravelDatatables;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class DatatablesServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
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
