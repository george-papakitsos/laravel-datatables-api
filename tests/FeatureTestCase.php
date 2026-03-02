<?php

namespace GPapakitsos\LaravelDatatables\Tests;

use GPapakitsos\LaravelDatatables\DatatablesServiceProvider;
use GPapakitsos\LaravelDatatables\Tests\Models\Country as Country;
use GPapakitsos\LaravelDatatables\Tests\Models\User as User;
use GPapakitsos\LaravelDatatables\Tests\Models\UserLogin as UserLogin;
use Orchestra\Testbench\TestCase;

class FeatureTestCase extends TestCase
{
    public string $route_prefix;
    public Country $country;
    public User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        User::factory()->has(UserLogin::factory()->count(rand(1, 5)))->count(49)->create();
        Country::factory()->count(10)->create();
        $this->country = Country::factory()->founded('1995-06-15')->create();
        $this->user = User::factory()->has(UserLogin::factory()->count(rand(10, 20)))->create([
            'name' => 'George Papakitsos',
            'email' => 'papakitsos_george@yahoo.gr',
            'country_id' => $this->country->id,
            'settings' => '{ "is_admin": true, "nickname": "papaki" }',
            'created_at' => '1981-04-23 10:00:00',
        ]);
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
    }

    protected function getRequestDataSample(): array
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
                ],
            ],
        ];
    }
}
