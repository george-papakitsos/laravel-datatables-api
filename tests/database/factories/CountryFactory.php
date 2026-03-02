<?php

namespace Database\Factories;

use GPapakitsos\LaravelDatatables\Tests\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Country::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'founded_at' => fake()->date(),
        ];
    }

    /**
     * Indicate that the user is suspended.
     */
    public function founded(string $founded_at): Factory
    {
        return $this->state(function (array $attributes) use ($founded_at) {
            return [
                'founded_at' => $founded_at,
            ];
        });
    }
}
