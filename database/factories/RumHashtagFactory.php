<?php

namespace Database\Factories;

use App\Models\Rum;
use Illuminate\Database\Eloquent\Factories\Factory;

class RumHashtagFactory extends Factory
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
            'hashtag' => $this->faker->word,
        ];
    }
}
