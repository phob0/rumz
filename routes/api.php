<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificationController;
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

            Route::group(['prefix' => 'favourite', 'as' => 'favourites'], function() {
                Route::patch('{rum_post}', [RumPostController::class, 'saveFavourite'])->name('saveFavouriteRumPost');
                Route::delete('remove/{rum_post}/{favourite}', [RumPostController::class, 'removeFavourite'])->name('removeFavouriteRumPost');
                Route::get('', [RumPostController::class, 'getFavourites'])->name('getFavouritesRumPost');
            });

            Route::group(['prefix' => 'comment', 'as' => 'comments'], function() {
                Route::patch('{rum_post}', [RumPostController::class, 'comment'])->name('commentRumPost');
                Route::patch('update/{rum_post}/{comment}', [RumPostController::class, 'updateComment'])->name('updateCommentRumPost');
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

    Route::group(['prefix' => 'notification', 'as' => 'notifications'], function() {
        Route::get('lookup', [NotificationController::class, 'lookup'])->name('lookupNotification');
        Route::get('all', [NotificationController::class, 'allNotifications'])->name('allNotification');
    });

    Route::get('/', [RumController::class, 'index'])->name('homepage');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::post('/register', [LoginController::class, 'register'])->name('register');

// URL for csrf cookie : http://localhost/sanctum/csrf-cookie

/*
 *
 * Test queries
 *
 * */
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
