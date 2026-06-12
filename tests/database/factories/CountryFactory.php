<?php

namespace Database\Factories;

use GPapakitsos\LaravelDatatables\Tests\Models\Locations\Country;
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
     * Sets country's founded_at field.
     */
    public function founded(string $founded_at): Factory
    {
        return $this->state(fn () => ['founded_at' => $founded_at]);
    }
}
