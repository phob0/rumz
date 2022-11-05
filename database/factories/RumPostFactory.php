<?php

namespace Database\Factories;

use App\Models\Rum;
use App\Models\User;
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
            'user_id' => User::all()->random()->id,
            'approved' => collect([0, 1])->random(),
            'description' => $this->faker->text,
            'metadata' => null
        ];
    }
}
