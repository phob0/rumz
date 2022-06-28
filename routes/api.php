<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RumController;
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
    //rum routes
    Route::group(['prefix' => 'rum', 'as' => 'rums'], function() {
        Route::post('create', [RumController::class, 'store'])->name('storeRum');
        Route::get('view/{rum}', [RumController::class, 'view'])->name('viewRum');
        Route::get('edit/{rum}', [RumController::class, 'edit'])->name('editRum');
        Route::put('update/{rum}', [RumController::class, 'update'])->name('updateRum');
        Route::delete('delete/{rum}', [RumController::class, 'delete'])->name('deleteRum');
        Route::get('hashtag-suggestions/{q?}', [RumController::class, 'hashtagSuggestions'])->name('hashtagSuggestions'); /* q param */
        Route::patch('join/{rum}/{type}', [RumController::class, 'join'])->name('joinRum')->whereIn('type', ['private', 'confidential', 'paid']);
        Route::patch('grant/{rum}/{user}', [RumController::class, 'grant'])->name('grantRum');
        Route::patch('reject/{rum}/{user}', [RumController::class, 'reject'])->name('rejectRum');
        Route::post('image', [RumController::class, 'image'])->name('imageRum');
    });

    Route::group(['prefix' => 'notification', 'as' => 'notifications'], function() {
        Route::get('lookup', [NotificationController::class, 'lookup'])->name('lookupNotification');
        Route::get('all', [NotificationController::class, 'allNotifications'])->name('allNotification');
    });

    Route::get('/', [RumController::class, 'index'])->name('homepage');
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
/*
 *
 * Test queries
 *
 * */
Route::get('/queries', function(Request $request) {
    $userId = User::where('id', '3e370e8d-4efb-4904-b561-665251247bfc')->first()->id;
    //user that belongs to rum > for policies
//    $userRum = Rum::whereHas('users', function (Builder $query) use($userId) {
//        $query->where('users.id', $userId)->where('users_rums.granted', 1);
//    })->where('type', Rum::TYPE_FREE)->get();
    $userRum = Rum::with('posts')->whereHas('users')->get();
    // rum posts with number of likes, users who liked, number of comments and comments
//    $posts = $userRum->posts;

    return 'queries';
});
