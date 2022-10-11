<?php

use Faker\Generator as Faker;
use GPapakitsos\LaravelDatatables\Tests\Models\User as User;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$user = new User();
$user->name = 'George Papakitsos';
$user->email = 'papakitsos_george@yahoo.gr';
$user->password = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm';
$user->created_at = '1981-04-23 10:00:00';
$user->updated_at = now();
$user->save();

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
        'created_at' => now(),
        'updated_at' => now(),
    ];
});
