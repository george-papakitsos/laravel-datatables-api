<?php

namespace GPapakitsos\LaravelDatatables\Tests;

use Carbon\Carbon;
use GPapakitsos\LaravelDatatables\DatatablesServiceProvider;
use Orchestra\Testbench\TestCase;

class FeatureTestCase extends TestCase
{
    public Models\Locations\Continent $continent;
    public Models\Locations\Country $country;
    public Models\User $user;
    public Models\UserLogin $userLogin;
    public string $route_prefix;
    public string $date_delimiter;
    public string $date_format;
    public string $null_delimiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Models\User::factory()->has(Models\UserLogin::factory()->count(rand(1, 5)))->count(49)->create();
        Models\Locations\Country::factory()->count(29)->create();

        $this->continent = Models\Locations\Continent::create(['name' => 'South America', 'abbreviation' => 'SA']);
        $this->country = Models\Locations\Country::factory()->founded('1995-06-15')->for($this->continent)->create();
        $this->user = Models\User::factory()->has(Models\UserLogin::factory()->count(rand(10, 20)))->create([
            'name' => 'George Papakitsos',
            'email' => 'papakitsos_george@yahoo.gr',
            'country_id' => $this->country->id,
            'settings' => '{ "is_admin": true, "nickname": "papaki" }',
            'created_at' => '1981-04-23 10:00:00',
            'taggable_type' => $this->continent::class,
            'taggable_id' => $this->continent->id,
        ]);
        $this->userLogin = Models\UserLogin::factory()->whenField(Carbon::now()->addMonth()->toDateTimeString())->for($this->user)->create();
    }

    protected function getPackageProviders($app)
    {
        return [
            DatatablesServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('datatables.models_namespace', 'GPapakitsos\LaravelDatatables\Tests\Models\\');

        $this->route_prefix = $app['config']->get('datatables.routes.prefix');
        $this->date_delimiter = $app['config']->get('datatables.filters.date_delimiter');
        $this->date_format = $app['config']->get('datatables.filters.date_format');
        $this->null_delimiter = $app['config']->get('datatables.filters.null_delimiter');
    }

    protected function getRequestDataSample(string $model = Models\User::class): array
    {
        $columns = array_merge(
            [
                [
                    'data' => 'id',
                    'searchable' => true,
                    'orderable' => false,
                    'search' => [
                        'value' => '',
                    ],
                ],
            ],
            match ($model) {
                Models\User::class => [
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
                    [
                        'data' => 'country',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                    [
                        'data' => 'userLogins',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                    [
                        'data' => 'settings',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                    [
                        'data' => 'userNameAndEmail',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                    [
                        'data' => 'latestUserLogins',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                    [
                        'data' => 'countryContinent',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                    [
                        'data' => 'taggable',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                ],
                Models\Locations\Country::class => [
                    [
                        'data' => 'name',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                    [
                        'data' => 'founded_at',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                    [
                        'data' => 'continent',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                ],
                Models\UserLogin::class => [
                    [
                        'data' => 'when',
                        'search' => [
                            'value' => '',
                        ],
                    ],
                ],
                default => [],
            }
        );

        return [
            'draw' => 1,
            'columns' => $columns,
            'start' => 0,
            'length' => 20,
            'search' => [
                'value' => '',
            ],
            'order' => [
                [
                    'column' => 1,
                    'dir' => 'asc',
                ],
            ],
        ];
    }
}
