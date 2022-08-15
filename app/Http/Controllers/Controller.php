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

    public function linkAccount(Request $request, User $user)
    {
        $stripe = $this->stripe->accountLinks->create([
            'account' => $user->stripe_id,
            'refresh_url' => env('APP_URL'),
            'return_url' => env('APP_URL'),
            'type' => 'account_onboarding',
        ]);

        return response()->json([
            'stripe_onboarding' => $stripe
        ]);
    }
}
