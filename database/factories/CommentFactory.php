<?php

namespace Database\Factories;

use App\Models\RumPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => function () {
                return User::all()->random()->id;
            },
            'post_id' => function () {
                return RumPost::all()->random()->id;
            },
            'comment' => $this->faker->text
        ];
    }
}
