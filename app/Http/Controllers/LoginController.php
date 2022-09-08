<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{

    private \Vonage\Client $vonage;

    public function __construct()
    {
        $this->vonage = new \Vonage\Client(new \Vonage\Client\Credentials\Container(
            new \Vonage\Client\Credentials\Basic(env('VONAGE_KEY'), env('VONAGE_SECRET'))
        ));
    }
    /*
     * validate register and send 2fa
     * validate 2fa and register
     *
     * same to login
     * */
    private function twoFactor($request)
    {
        $vonageRequest = new \Vonage\Verify\Request($request->phone, env("VONAGE_APP_NAME"));

        $response = $this->vonage->verify()->start($vonageRequest);

        return $response->getRequestId();
    }

    private function twoFactorValidate($request)
    {
        $result = $this->vonage->verify()->check($request->vonage_id, $request->vonage_code);

        // status 16 = error
        // status 0 = success

        return $result->getResponseData();
    }

    public function preRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
//            'sex' => [
//                'required',
//                Rule::in(['male', 'female'])
//            ],
//            'birth_date' => 'required|date',
//            'email' => 'required|email',
//            'password' => 'required'
        ]);

        $vonage = $this->twoFactor($request);

        return response()->json(['user' => $request->all(), 'vonage' => $vonage]);
    }

    public function register(Request $request)
    {
        $vonage = $this->twoFactorValidate($request);

        if ($vonage['status'] !== 0) {
            throw new HttpResponseException(
                response()->json(['errors' => $vonage], Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
//            'sex' => $request->sex,
//            'birth_date' => $request->birth_date,
            'email' => '',
            'stripe_id' => '',
//            'password' => Hash::make($request->password)
        ]);

//        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

//        $stripe->accounts->create([
//            'type' => 'custom',
//            'country' => 'US',
//            'email' => $user->email,
//            'capabilities' => [
//                'card_payments' => ['requested' => true],
//                'transfers' => ['requested' => true],
//            ],
//        ]);

//        $user->update([
//            'stripe_id' => $stripe->id,
//            'pm_type' => $stripe->type
//        ]);

        return response()->json([
            'user' => $user
        ]);
    }

    public function preLogin(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'phone' => 'required|exists:users'
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        $vonage = $this->twoFactor($request);

        return response()->json(['user' => $user, 'vonage' => $vonage]);
    }

    public function login(Request $request)
    {
        $vonage = $this->twoFactorValidate($request);

        if ($vonage['status'] == 0) {
            $user = User::where('phone', $request->phone)->first();

            return $user->createToken('sanctum-token')->plainTextToken;
        } else {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }
    }

    public function logout(): \Illuminate\Http\Response
    {
        auth()->user()->tokens()->delete();

        return response()->noContent();
    }
}
