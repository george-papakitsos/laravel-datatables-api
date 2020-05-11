<?php

namespace GPapakitsos\LaravelDatatables\Tests;

use Orchestra\Testbench\TestCase;
use GPapakitsos\LaravelDatatables\DatatablesServiceProvider;
use GPapakitsos\LaravelDatatables\Tests\Models\User as User;

class FeatureTestCase extends TestCase
{
	public $route_prefix;

	protected function setUp(): void
	{
		parent::setUp();

		$this->loadMigrationsFrom(__DIR__.'/database/migrations');
		$this->withFactories(__DIR__.'/database/factories');

		factory(User::class, 50)->create();
	}

	protected function getPackageProviders($app)
	{
		return [
			DatatablesServiceProvider::class,
		];
	}

	protected function getEnvironmentSetUp($app)
	{
		$config = $app->get('config');

		$config->set('database.default', 'testbench');
		$config->set('database.connections.testbench', [
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => '',
		]);

		$this->route_prefix = 'datatable';
		$config->set('datatables', [
			'models_namespace' => 'GPapakitsos\LaravelDatatables\Tests\Models\\',
			'routes' => [
				'prefix' => $this->route_prefix,
			],
			'filters' => [
				'date_format' => 'd/m/Y',
				'date_delimiter' => '-dateDelimiter-',
			],
		]);
	}

	protected function getRequestDataSample()
	{
		return [
			'draw' => 1,
			'columns' => [
				[
					'data' => 'id',
					'searchable' => true,
					'orderable' => false,
					'search' => [
						'value' => '',
					],
				],
				[
					'data' => 'name',
					'search' => [
						'value' => '',
					],
				],
				[
					'data' => 'email',
					'search' => [
						'value' => '',
					],
				],
				[
					'data' => 'created_at',
					'search' => [
						'value' => '',
					],
				],
				[
					'data' => 'updated_at',
					'search' => [
						'value' => '',
					],
				],
			],
			'start' => 0,
			'length' => 20,
			'search' => [
				'value' => '',
			],
			'order' => [
				[
					'column' => 1,
					'dir' => 'asc',
				]
			],
			'column_names' => ['id', 'name', 'email', 'created_at', 'updated_at'],
		];
	}
}
