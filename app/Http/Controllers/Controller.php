<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

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

    public function image(Request $request): \Illuminate\Http\JsonResponse
    {
        $file = $request->file('image');

        if(is_null($file)) {
            throw new HttpResponseException(
                response()->json(['error' => 'Please upload a legit image file.'], Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        $path = $file->store('public/images/temp');

        return response()->json([
            'path' => $path,
            'file_name' => $file->hashName()
        ]);
    }

    public function deleteImage(Request $request, Image $image): \Illuminate\Http\Response
    {
        // TODO: add gate conditions
        if (Storage::disk('local')->exists(public_image_path($request->image))) {
            Storage::disk('local')->delete(public_image_path($request->image));
        }

        $image->delete();

        return response()->noContent();
    }

    protected function removeImage($path)
    {
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    protected function sendSMS($number, $message)
    {
        $basic  = new \Vonage\Client\Credentials\Basic(env('VONAGE_KEY'), env('VONAGE_SECRET'));
        $client = new \Vonage\Client($basic);

        $response = $client->sms()->send(
        new \Vonage\SMS\Message\SMS($number, env('app_name'), $message)
        );

        $message = $response->current();

        if ($message->getStatus() !== 0) {
            throw new HttpResponseException(
                response()->json(['error' => [
                    'message' => 'This error is from our SMS service.',
                    'error' => $message->getStatus()
                ]], Response::HTTP_EXPECTATION_FAILED)
            );
        }
    }
}
