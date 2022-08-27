<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function createStripeAccount(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        $account = $stripe->accounts->create([
            'type' => 'express',
            'country' => 'US',
            'email' => $user->email,
            'capabilities' => [
//                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
            'business_profile' => [
                'url' => 'https://rumz.com'
            ]
        ]);

        $user->update([
            'stripe_id' => $account->id
        ]);

        return response()->json([
            'stripe_account' => $account
        ]);
    }

    public function linkAccount(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        $account = $stripe->accountLinks->create([
            'account' => $user->stripe_id,
            'refresh_url' => env('APP_URL'),
            'return_url' => env('APP_URL'),
            'type' => 'account_onboarding',
        ]);

        return response()->json([
            'stripe_onboarding' => $account
        ]);
    }
}
