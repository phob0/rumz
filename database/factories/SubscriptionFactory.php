<?php

namespace Database\Factories;

use App\Models\Rum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::all()->random()->id,
            'rum_id' => Rum::all()->random()->id,
            'owner_amount' => 10.00,
            'profit' => 2.00,
            'transfer_id' => '',
            'amount' => collect([1, 2, 3, 4])->random(),
            'is_paid' => 1,
            'granted' => 1,
            'expire_at' => null
        ];
    }
}
