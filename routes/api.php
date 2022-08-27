<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RumController;
use App\Http\Controllers\RumPostController;
use App\Models\Rum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\DomCrawler\Crawler;

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
        Route::get('my-rums', [RumController::class, 'myRums'])->name('myRums');
        Route::get('explore', [RumController::class, 'explore'])->name('exploreRum');
        Route::get('current-rums', [RumController::class, 'currentRums'])->name('currentRums');
        Route::post('create', [RumController::class, 'store'])->name('storeRum');
        Route::get('view/{rum}', [RumController::class, 'view'])->name('viewRum');
        Route::get('edit/{rum}', [RumController::class, 'edit'])->name('editRum');
        Route::put('update/{rum}', [RumController::class, 'update'])->name('updateRum');
        Route::delete('delete/{rum}', [RumController::class, 'delete'])->name('deleteRum');
        Route::get('members/{rum}', [RumController::class, 'membersList'])->name('membersListRum');
        Route::get('hashtag-suggestions/{q?}', [RumController::class, 'hashtagSuggestions'])->name('hashtagSuggestions'); /* q param */
        Route::patch('join/{rum}/{type}', [RumController::class, 'join'])->name('joinRum')->whereIn('type', ['private', 'confidential', 'paid', 'free']);
        Route::patch('grant/{rum}/{user}', [RumController::class, 'grant'])->name('grantRum');
        Route::patch('reject/{rum}/{user}', [RumController::class, 'reject'])->name('rejectRum');
        Route::patch('report/{rum}', [RumController::class, 'reportRum'])->name('reportRum');
        Route::group(['prefix' => 'member', 'as' => 'members'], function() {
            Route::patch('ban-unban/{action}/{rum}/{user}', [RumController::class, 'banUnbanMember'])->name('banUnbanMemberRum')->whereIn('action', ['ban', 'unban']);
            Route::patch('invite/{rum}/{user}', [RumController::class, 'inviteMember'])->name('inviteMemberRum');
            Route::patch('accept-invite/{rum}', [RumController::class, 'acceptInviteMember'])->name('acceptInviteMemberRum');
            Route::patch('remove/{rum}/{user}', [RumController::class, 'removeMember'])->name('removeMemberRum');
        });
        Route::post('image', [RumController::class, 'image'])->name('imageRum');
        Route::get('search/{q?}', [RumController::class, 'search'])->name('searchRum');

        Route::group(['prefix' => 'post', 'as' => 'posts'], function() {
            Route::post('create', [RumPostController::class, 'store'])->name('storeRumPost');
            Route::get('edit/{rum_post}', [RumPostController::class, 'edit'])->name('editRumPost');
            Route::put('update/{rum_post}', [RumPostController::class, 'update'])->name('updateRumPost');
            Route::patch('report/{rum_post}', [RumPostController::class, 'reportPost'])->name('reportRumPost');
            Route::patch('like-dislike/{action}/{type}/{id}', [RumPostController::class, 'likeOrDislike'])
                ->whereIn('type', ['post', 'comment', 'reply'])
                ->whereIn('action', ['like', 'dislike'])
                ->name('likeRumPost');
            Route::delete('delete/{rum_post}', [RumPostController::class, 'delete'])->name('deleteRumPost');

            Route::group(['prefix' => 'favourite', 'as' => 'favourites'], function() {
                Route::patch('{rum_post}', [RumPostController::class, 'saveFavourite'])->name('saveFavouriteRumPost');
                Route::delete('remove/{rum_post}/{favourite}', [RumPostController::class, 'removeFavourite'])->name('removeFavouriteRumPost');
                Route::get('', [RumPostController::class, 'getFavourites'])->name('getFavouritesRumPost');
            });

            Route::group(['prefix' => 'comment', 'as' => 'comments'], function() {
                Route::patch('{rum_post}', [RumPostController::class, 'comment'])->name('commentRumPost');
                Route::patch('update/{rum_post}/{comment}', [RumPostController::class, 'updateComment'])->name('updateCommentRumPost');
                Route::patch('report/{rum_post}/{comment}', [RumPostController::class, 'reportComment'])->name('reportCommentRumPost');
                Route::delete('delete/{rum_post}/{comment}', [RumPostController::class, 'deleteComment'])->name('deleteCommentRumPost');

                Route::group(['prefix' => 'reply', 'as' => 'replies'], function() {
                    Route::patch('{rum_post}/{comment}', [RumPostController::class, 'replyComment'])->name('replyCommentRumPost');
                    Route::patch('update/{rum_post}/{comment}/{comment_reply}', [RumPostController::class, 'updateReply'])->name('updateReplyRumPost');
                    Route::patch('report/{rum_post}/{comment}/{comment_reply}', [RumPostController::class, 'reportReply'])->name('reportReplyRumPost');
                    Route::delete('delete/{rum_post}/{comment}/{comment_reply}', [RumPostController::class, 'deleteReply'])->name('deleteReplyRumPost');
                });
            });
            Route::post('lookup-metadata', [RumPostController::class, 'lookupMetadata'])->name('lookupMetadata');
            Route::get('{rum}', [RumPostController::class, 'index'])->name('postPage');
        });
    });

    Route::group(['prefix' => 'profile', 'as' => 'profiles'], function() {
        Route::get('', [ProfileController::class, 'profile']);
        Route::get('posts', [ProfileController::class, 'posts']);
    });

    Route::group(['prefix' => 'notification', 'as' => 'notifications'], function() {
        Route::get('lookup', [NotificationController::class, 'lookup'])->name('lookupNotification');
        Route::get('all', [NotificationController::class, 'allNotifications'])->name('allNotification');
    });

    Route::get('/', [RumController::class, 'index'])->name('homepage');

    /*
     * TODO: check balance stripe
     */

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::get('/create-stripe-account/{user}', [Controller::class, 'createStripeAccount'])->name('createStripeAccount');

Route::get('/link-account/{user}', [Controller::class, 'linkAccount'])->name('linkAccount');

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::post('/register', [LoginController::class, 'register'])->name('register');

// URL for csrf cookie : http://localhost/sanctum/csrf-cookie

/*
 *
 * Test queries
 *
 * */
Route::get('/reauth', function(Request $request) {
    dd('reauth from completing the stripe connect setup account.');
});
Route::get('/return', function(Request $request) {
    dd('return back from the stripe connect setup account wizzard.');
});
Route::get('/test_stripe', function(Request $request) {
    $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

//    return \Stripe\Charge::create([
//        'amount'   => 1000,
//        'currency' => 'usd',
//        'source' => 'acct_1LTbzoPLj7mSkbe9',
//        'description' => 'For admin Rum'
//    ]);

//    return \Stripe\PaymentIntent::create([
//        'amount' => 1000,
//        'currency' => 'usd',
//        'application_fee_amount' => 100,
//        'payment_method_types' => ['customer_balance'],
//        'payment_method' => 'pm_1LToqbAB46hIM0CMuDLz6NbE',
//        'confirm' => true,
//    ], ['stripe_account' => 'acct_1LTnIOPJhHLfy5Xm']);

    $charge =  \Stripe\Charge::create([
        "amount" => 1000,
        "currency" => "usd",
//        "source" => "tok_visa",
        "source" => "acct_1LTnIOPJhHLfy5Xm",
//        for simple card charge
//        "transfer_data" => [
//            "amount" => 877,
//            "destination" => "acct_1LTe3uPLLPTwYFpQ",
//        ],
    ]);

    return \Stripe\Transfer::create([
        "amount" => 900,
        "currency" => "usd",
        "source_transaction" => $charge->id,
        "destination" => "acct_1LTe3uPLLPTwYFpQ",
    ]);

//    return $stripe->refunds->create([
//        'charge' => 'py_1LTc3zAB46hIM0CMXf9zoye6',
//    ]);

//
//    return \Stripe\Balance::retrieve(
//        ['stripe_account' => 'acct_1LSVh9PIFtlUyXPr']
//    );

    /*
    return \Stripe\PaymentLink::create([
            'line_items' => [
                [
                    'price' => 'price_1LTBmHAB46hIM0CMbNuIpb66',
                    'quantity' => 1,
                ],
            ],
              'on_behalf_of' => 'acct_1LT9d7PBVmwPBmxm', //sharebaan account
              'transfer_data' => [
                    'destination' => 'acct_1LSVh9PIFtlUyXPr', //sharebaanda account
                ],
    ]);
*/
//    return $stripe->accounts->all();
//    return $stripe->products->all(['limit' => 3]);

//    return $stripe->prices->create([
//        'unit_amount' => 5000,
//        'currency' => 'ron',
//        'recurring' => ['interval' => 'month'],
//        'product' => 'prod_MBX02wUd9bAV6Q',
//    ]);
//    return $stripe->prices->all(['limit' => 3]);
//    $stripe->accounts->delete(
//        'acct_1LSTQDPDPEw2ehn1',
//        []
//    );
});
Route::get('/queries', function(Request $request) {
//    $userId = User::where('id', '3e370e8d-4efb-4904-b561-665251247bfc')->first()->id;
    //user that belongs to rum > for policies
//    $userRum = Rum::whereHas('users', function (Builder $query) use($userId) {
//        $query->where('users.id', $userId)->where('users_rums.granted', 1);
//    })->where('type', Rum::TYPE_FREE)->get();
//    $userRum = Rum::with('posts')->whereHas('users')->get();
    // rum posts with number of likes, users who liked, number of comments and comments
//    $posts = $userRum->posts;

    //check valid address
    $crawler = Goutte::request('GET', 'https://9gag.com/gag/ay9rmpV');
    $r = $crawler->filterXpath("//meta[@property='og:title']")->extract(['content']);
    var_dump($r);
    //    $description = $crawler->filterXpath('//meta[@property="og:description"]')->attr('content');
//    $image = $crawler->filterXpath('//meta[@property="og:description"]')->extract('content');
//    var_dump($image);
    ///gag/ay9rmpV
//    $url = "https://9gag.com/gag/ay9rmpV";
//    var_dump($url);
//    $html = file_get_contents($url);
//    $crawler = new Crawler($html);
//
//    $data = $crawler->filterXpath("//meta[@property='og:title']")->extract(['content']);
//    var_dump($data);
//    $title = $crawler->filterXpath('//meta[@property="og:site_name"]');
    //title or site_name for homepage
    //description
    //image or video
    //url
//    try {
//        $image->attr('content');
//    } catch (InvalidArgumentException $e) {
//        $image = false;
//    }

    //try catch validator
    // homepage url checker

    return 'queries';
});
