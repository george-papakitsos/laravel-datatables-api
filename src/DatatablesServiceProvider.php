<?php

namespace GPapakitsos\LaravelDatatables;

use Illuminate\Support\ServiceProvider;

class DatatablesServiceProvider extends ServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		if ($this->app->runningInConsole())
		{
			$this->publishes([
				__DIR__.'/../config/datatables.php' => config_path('datatables.php'),
			]);
		}
	}
}
