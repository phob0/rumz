<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /*
     * TODO
     *  total friends
     *  upload avatar
     *  retrieve ith avatar
     */

    public function profile(Request $request): JsonResource
    {
        $user =  User::withCount('rums')->find(auth()->user()->id);
        $user->total_members = $user->rums->sum('members');

        return JsonResource::make($user);
    }

    public function posts(): JsonResource
    {
        return JsonResource::make(auth()->user()->posts);
    }

    public function update(UpdateProfileRequest $request): \Illuminate\Http\Response
    {
        $data = $request->validated();

        $path = !is_null($request->file('image')) ? $request->file('image')->store('public/images/profiles') : null;

        $data['image'] = link_image_path($path);

        auth()->user()->update(
            Arr::except($data, 'image')
        );

        $profile = auth()->user();

        if (is_null(auth()->user()->image) && !is_null($data['image'])) {
            $profile->image()->create([
                'url' => $data['image'],
                'imageable_id' => $profile->id,
                'imageable_type' => User::class,
            ]);
        } else if (
            (!is_null(auth()->user()->image) && !is_null($data['image'])) &&
            get_image_name(auth()->user()->image->url) !== get_image_name($data['image']))
        {

            $this->removeImage(public_image_path(auth()->user()->image->url));

            $profile->image()->update([
                'url' => $data['image'],
                'imageable_id' => $profile->id,
                'imageable_type' => User::class,
            ]);
        }

        if(is_null($profile->stripe_id) || $profile->stripe_id == "") {
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

            $account = $stripe->accounts->create([
                'type' => 'custom',
                'country' => 'US',
                'email' => $profile->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);

            $profile->update([
                'stripe_id' => $account->id,
                'pm_type' => $account->type
            ]);
        }

        return response()->noContent();
    }

    public function onboardingStripe(Request $request): \Illuminate\Http\JsonResponse
    {
        if (is_null(auth()->user()->stripe_id) || auth()->user()->stripe_id == "") {
            return response()->json(['error' => 'Please update your profile for your stripe account to be created.']);
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        return response()->json([
            'stripe_onboarding_response' => $stripe->accountLinks->create([
                'account' => auth()->user()->stripe_id,
                'refresh_url' => env('APP_URL') . '/profile/reauth-onboarding',
                'return_url' => env('APP_URL') . '/profile/return-onboarding',
                'type' => 'account_onboarding',
            ])
        ]);
    }

    public function returnOnboarding(Request $request): \Illuminate\Http\JsonResponse
    {
        auth()->user()->update([
            'stripe_onboarding' => true
        ]);

        return response()->json(['info' => 'Your stripe onboarding is now complete.']);
    }

    public function addBalance(Request $request)
    {}

}
