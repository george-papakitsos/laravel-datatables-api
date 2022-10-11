<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use GPapakitsos\LaravelDatatables\Tests\Models\UserLogin;

class UserLoginFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserLogin::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'when' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
        ];
    }
}
