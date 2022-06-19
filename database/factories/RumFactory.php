<?php

namespace Database\Factories;

use App\Models\Rum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $type = collect([
            Rum::TYPE_FREE,
            Rum::TYPE_PAID,
            Rum::TYPE_PRIVATE,
            Rum::TYPE_CONFIDENTIAL,
        ]);

        $privilege = collect([
            Rum::FOR_ALL,
            Rum::FOR_ME,
            Rum::FOR_MEMBERS,
        ]);

        return [
            'user_id' => User::where('email', 'user@rumz.com')->first()->id,
            'title' => $this->faker->text,
            'description' => $this->faker->text,
            'image' => $this->faker->imageUrl('300', '300', null, false, env('APP_NAME')),
            'type' => $type->random(),
            'privilege' => $privilege->random(),
        ];
    }
}
