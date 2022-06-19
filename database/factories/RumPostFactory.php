<?php

namespace Database\Factories;

use App\Models\Rum;
use Illuminate\Database\Eloquent\Factories\Factory;

class RumPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'rum_id' => Rum::all()->random()->id,
            'approved' => collect([0, 1])->random(),
            'title' => $this->faker->text,
            'description' => $this->faker->text,
        ];
    }
}
