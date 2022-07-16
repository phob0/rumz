<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
    public function register(Request $request)
    {
        // TODO: implement 2 factor reistration

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'sex' => [
                'required',
                Rule::in(['male', 'female'])
            ],
            'birth_date' => 'required|date',
            'email' => 'required|email',
            'password' => 'required'
        ]);

        User::create([
            'name' => $request->email,
            'phone' => $request->phone,
            'sex' => $request->sex,
            'birth_date' => $request->birth_date,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->noContent();
    }

    public function login(Request $request)
    {
        // TODO: implement 2 factor authentication

        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user->createToken('sanctum-token')->plainTextToken;
    }

    public function logout(): \Illuminate\Http\Response
    {
        auth()->user()->tokens()->delete();

        return response()->noContent();
    }
}
