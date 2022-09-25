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

        if(is_null($profile->stripe_id)) {
            if ($profile->email == "") {
                return response()->json(['warning' => 'To create your stripe account please provide an email address.']);
            }

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

    public function addBalance(Request $request)
    {}

}
