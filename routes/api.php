<?php

use App\Models\Rum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/login', function () {
    return 'login page';
})->name('login');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('user');

    Route::get('/', function () {
        return 'welcome';
    });
});

Route::post('/login', function (Request $request) {
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
});

Route::get('/queries', function(Request $request) {
    $userId = User::first()->id;

    //user that belongs to rum > for policies
    $userRum = Rum::whereHas('users', function (Builder $query) use($userId) {
        $query->where('users.id', $userId)->where('users_rums.granted', 1);
    })->where('type', Rum::TYPE_FREE)->first();
    // rum posts with number of likes, users who liked, number of comments and comments
    $posts = $userRum->posts;

    return $posts;
});
