<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\FriendController;
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
use Illuminate\Support\Facades\Log;
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
            // add group for admin invite
            Route::group(['prefix' => 'invite', 'as' => 'invites'], function() {
                Route::patch('{rum}/{user}', [RumController::class, 'inviteMember'])->name('inviteMemberRum');
                Route::patch('admin/{rum}/{user}', [RumController::class, 'inviteAdminMember'])->name('inviteAdminMemberRum');
                Route::patch('accept/admin/{rum}', [RumController::class, 'acceptInviteMember'])->name('acceptInviteMemberRum');
                Route::patch('accept/{rum}', [RumController::class, 'acceptAdminInviteMember'])->name('acceptAdminInviteMemberRum');
            });

            Route::patch('remove/{rum}/{user}', [RumController::class, 'removeMember'])->name('removeMemberRum');
        });
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

    Route::group(['prefix' => 'image', 'as' => 'images'], function() {
        Route::post('', [Controller::class, 'image'])->name('image');
        Route::delete('{image}', [Controller::class, 'deleteImage'])->name('deleteImage');
    });

    Route::group(['prefix' => 'profile', 'as' => 'profiles'], function() {
        Route::get('', [ProfileController::class, 'profile'])->name('profile');
        Route::get('posts', [ProfileController::class, 'posts'])->name('profilePosts');
        Route::post('update', [ProfileController::class, 'update'])->name('profileUpdate');
        Route::get('stripe-onboarding', [ProfileController::class, 'onboardingStripe'])->name('profileOnboarding');

        Route::get('/return-onboarding', [ProfileController::class, 'returnOnboarding'])->name('profileReturnOnboarding');
        Route::get('/reauth-onboarding', function(Request $request) {
            return response()->json(['warning' => 'Your stripe onboarding link has expired, please try again']);
        });

        /*
         * TODO: check balance stripe
         */
    });

    Route::group(['prefix' => 'friend', 'as' => 'friends'], function() {
        Route::get('lookup-friends', [FriendController::class, 'lookupFriends'])->name('lookupFriends');
    });

    Route::group(['prefix' => 'notification', 'as' => 'notifications'], function() {
        Route::get('lookup', [NotificationController::class, 'lookup'])->name('lookupNotification');
        Route::get('all', [NotificationController::class, 'allNotifications'])->name('allNotification');
        Route::get('mark-as-read', [NotificationController::class, 'markAsReadNotification'])->name('markAsReadNotification');
        Route::patch('mark-as-old/{notification}', [NotificationController::class, 'markAsOldNotification'])->name('markAsOldNotification');
    });

    Route::get('/', [RumController::class, 'index'])->name('homepage');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::get('/create-stripe-account/{user}', [Controller::class, 'createStripeAccount'])->name('createStripeAccount');

Route::get('/link-account/{user}', [Controller::class, 'linkAccount'])->name('linkAccount');

Route::post('/pre-login', [LoginController::class, 'preLogin'])->name('preLogin');

Route::post('/login', [LoginController::class, 'login'])->name('login');


Route::post('/pre-register', [LoginController::class, 'preRegister'])->name('preRegister');

Route::post('/register', [LoginController::class, 'register'])->name('register');

Route::post('/dev-login', function(Request $request) {
    $user = User::where('phone', $request->phone)->first();

    return $user->createToken('sanctum-token')->plainTextToken;
});
//Route::post('/2fa/request', [LoginController::class, 'twoFactor'])->name('twoFactor');
//Route::post('/2fa/validate', [LoginController::class, 'twoFactorValidate'])->name('twoFactorValidate');

// URL for csrf cookie : http://localhost/sanctum/csrf-cookie

/*
 *
 * Test queries
 *
 * */

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
//http://80.240.26.248/test/support-webhook
Route::post('/test/support-webhook', function(Request $request) {
    Log::debug('Mandrill webhook test.', $request->all());
    return true;
});

Route::get('/queries', function(Request $request) {
//    $mailchimp = new MailchimpTransactional\ApiClient();
//    $mailchimp->setApiKey(env('MAILCHIMP_MANDRILL_KEY'));
//    $response = $mailchimp->messages->search();
//
//    dd($response);

    $json2 = json_decode( "[{\"event\":\"inbound\",\"ts\":1666038233,\"msg\":{\"raw_msg\":\"Received: from mail-oi1-f179.google.com (unknown [209.85.167.179])\\n\\tby relay-7.us-west-2.relay-prod (Postfix) with ESMTPS id 3FD8825D2C\\n\\tfor <support@rumz.com>; Mon, 17 Oct 2022 20:23:53 +0000 (UTC)\\nReceived: by mail-oi1-f179.google.com with SMTP id w196so13398784oiw.8\\n        for <support@rumz.com>; Mon, 17 Oct 2022 13:23:53 -0700 (PDT)\\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed\\/relaxed;\\n        d=gmail.com; s=20210112;\\n        h=to:subject:message-id:date:from:in-reply-to:references:mime-version\\n         :from:to:cc:subject:date:message-id:reply-to;\\n        bh=iXLGQrzQ2pgT7Wf8u3xFHyzeB1li9MsiHRpbEb8J7OU=;\\n        b=ixyEmO9kzNux\\/k4Fa6h1Um8ZN2VSbQOP\\/f+WatlPbJZURs\\/wl+M51PlbJSqbjBPEyU\\n         swyVMrnBwvUywiG+rW4obPa3ODO\\/rE0XWdCzFn+cuXkucAAiP5mKP75qjr0aOqRW5Kni\\n         u9v9+CveEwPjniZ\\/YDfX0sa4CuLthhZ4QzarmpFOalfcUevGLwW6Xo48\\/MESuQv1CzdR\\n         I0Bp+oSsm5PDX0vv6vBBwSyFwQnkU4yEypjKdpnWG0hR7tTxgdiEs6fyOkPjxIZfdER6\\n         ao6XToOAHpl4EWRy4mFcIsyzCr06wH6jNlj255puTYb8Ost9+LX95ED8daXVv15rUAqd\\n         PznQ==\\nX-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed\\/relaxed;\\n        d=1e100.net; s=20210112;\\n        h=to:subject:message-id:date:from:in-reply-to:references:mime-version\\n         :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to;\\n        bh=iXLGQrzQ2pgT7Wf8u3xFHyzeB1li9MsiHRpbEb8J7OU=;\\n        b=OYj2Q4OLAkUEi6OGLkII0KOLJWrObZc5yXgM3Yq+SFcCgwsSqSKI\\/0+Db\\/9iwH4C5H\\n         vTKvY1j5FmNYbaEcb9tUaRg2eAgyNQWPvTfmvr63XJcug5fc2RjVjhtZTiStTtOQ8MnV\\n         P6ICCvaEisbFbrkLim\\/e7QAlBSauY6I92SDp4K7pP\\/3b4Y5UjQ1eb4wxsfes5nG1+OIZ\\n         AHNK\\/hjjABJqqR5+TuS0\\/hl318FElThKm7b2g7RlnLylDpcdbOzZFwKiqj+Ocer6ioeP\\n         SP0fjbPArwDpy7dpzMEoJpAk1fqLGZcYXZzgm04YxxdCpkBXGhpr+TPxSJDBVN0HAyuY\\n         2Yqg==\\nX-Gm-Message-State: ACrzQf2RTUel7R7rXVsjsJqBBXymyTzAYKgM1rVg5Sb0agdcoO8Kw0P2\\n\\tMmUblAETlrqnzB8a112g6p71hx1o24WMWZlWfBY5EFzE\\nX-Google-Smtp-Source: AMsMyM6hML3CpCLsul4hF2MJuLMvPhBwPabbDApUc5KatFhJInJE7HLjx4eX8+zME13hawy2cGNlTFImJI4Csb7PeyU=\\nX-Received: by 2002:a05:6808:200b:b0:355:2801:fe4a with SMTP id\\n q11-20020a056808200b00b003552801fe4amr5840783oiw.30.1666038232433; Mon, 17\\n Oct 2022 13:23:52 -0700 (PDT)\\nMIME-Version: 1.0\\nReferences: <fd5cc787053dd3dae16bca797ae04698@rumz.com>\\nIn-Reply-To: <fd5cc787053dd3dae16bca797ae04698@rumz.com>\\nFrom: sbaan da <sharebaanda@gmail.com>\\nDate: Mon, 17 Oct 2022 23:23:41 +0300\\nMessage-ID: <CAEHgj6K_=c_39SVLdqWLEuYvhOuC8hvRWi-AGz7SFxVmQCgVsA@mail.gmail.com>\\nSubject: Re: test\\nTo: support@rumz.com\\nContent-Type: multipart\\/alternative; boundary=\\\"00000000000006c96d05eb40c1e6\\\"\\n\\n--00000000000006c96d05eb40c1e6\\nContent-Type: text\\/plain; charset=\\\"UTF-8\\\"\\n\\ncheck logs with reply\\n\\nOn Mon, Oct 17, 2022 at 11:22 PM <support@rumz.com> wrote:\\n\\n> Hi!\\n>\\n\\n--00000000000006c96d05eb40c1e6\\nContent-Type: text\\/html; charset=\\\"UTF-8\\\"\\nContent-Transfer-Encoding: quoted-printable\\n\\n<div dir=3D\\\"ltr\\\">check logs with reply<\\/div><br><div class=3D\\\"gmail_quote\\\">=\\n<div dir=3D\\\"ltr\\\" class=3D\\\"gmail_attr\\\">On Mon, Oct 17, 2022 at 11:22 PM &lt;=\\n<a href=3D\\\"mailto:support@rumz.com\\\">support@rumz.com<\\/a>&gt; wrote:<br><\\/di=\\nv><blockquote class=3D\\\"gmail_quote\\\" style=3D\\\"margin:0px 0px 0px 0.8ex;borde=\\nr-left:1px solid rgb(204,204,204);padding-left:1ex\\\">Hi!<br>\\n<\\/blockquote><\\/div>\\n\\n--00000000000006c96d05eb40c1e6--\",\"headers\":{\"Received\":[\"from mail-oi1-f179.google.com (unknown [209.85.167.179]) by relay-7.us-west-2.relay-prod (Postfix) with ESMTPS id 3FD8825D2C for <support@rumz.com>; Mon, 17 Oct 2022 20:23:53 +0000 (UTC)\",\"by mail-oi1-f179.google.com with SMTP id w196so13398784oiw.8 for <support@rumz.com>; Mon, 17 Oct 2022 13:23:53 -0700 (PDT)\"],\"Dkim-Signature\":\"v=1; a=rsa-sha256; c=relaxed\\/relaxed; d=gmail.com; s=20210112; h=to:subject:message-id:date:from:in-reply-to:references:mime-version :from:to:cc:subject:date:message-id:reply-to; bh=iXLGQrzQ2pgT7Wf8u3xFHyzeB1li9MsiHRpbEb8J7OU=; b=ixyEmO9kzNux\\/k4Fa6h1Um8ZN2VSbQOP\\/f+WatlPbJZURs\\/wl+M51PlbJSqbjBPEyU swyVMrnBwvUywiG+rW4obPa3ODO\\/rE0XWdCzFn+cuXkucAAiP5mKP75qjr0aOqRW5Kni u9v9+CveEwPjniZ\\/YDfX0sa4CuLthhZ4QzarmpFOalfcUevGLwW6Xo48\\/MESuQv1CzdR I0Bp+oSsm5PDX0vv6vBBwSyFwQnkU4yEypjKdpnWG0hR7tTxgdiEs6fyOkPjxIZfdER6 ao6XToOAHpl4EWRy4mFcIsyzCr06wH6jNlj255puTYb8Ost9+LX95ED8daXVv15rUAqd PznQ==\",\"X-Google-Dkim-Signature\":\"v=1; a=rsa-sha256; c=relaxed\\/relaxed; d=1e100.net; s=20210112; h=to:subject:message-id:date:from:in-reply-to:references:mime-version :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to; bh=iXLGQrzQ2pgT7Wf8u3xFHyzeB1li9MsiHRpbEb8J7OU=; b=OYj2Q4OLAkUEi6OGLkII0KOLJWrObZc5yXgM3Yq+SFcCgwsSqSKI\\/0+Db\\/9iwH4C5H vTKvY1j5FmNYbaEcb9tUaRg2eAgyNQWPvTfmvr63XJcug5fc2RjVjhtZTiStTtOQ8MnV P6ICCvaEisbFbrkLim\\/e7QAlBSauY6I92SDp4K7pP\\/3b4Y5UjQ1eb4wxsfes5nG1+OIZ AHNK\\/hjjABJqqR5+TuS0\\/hl318FElThKm7b2g7RlnLylDpcdbOzZFwKiqj+Ocer6ioeP SP0fjbPArwDpy7dpzMEoJpAk1fqLGZcYXZzgm04YxxdCpkBXGhpr+TPxSJDBVN0HAyuY 2Yqg==\",\"X-Gm-Message-State\":\"ACrzQf2RTUel7R7rXVsjsJqBBXymyTzAYKgM1rVg5Sb0agdcoO8Kw0P2 MmUblAETlrqnzB8a112g6p71hx1o24WMWZlWfBY5EFzE\",\"X-Google-Smtp-Source\":\"AMsMyM6hML3CpCLsul4hF2MJuLMvPhBwPabbDApUc5KatFhJInJE7HLjx4eX8+zME13hawy2cGNlTFImJI4Csb7PeyU=\",\"X-Received\":\"by 2002:a05:6808:200b:b0:355:2801:fe4a with SMTP id q11-20020a056808200b00b003552801fe4amr5840783oiw.30.1666038232433; Mon, 17 Oct 2022 13:23:52 -0700 (PDT)\",\"Mime-Version\":\"1.0\",\"References\":\"<fd5cc787053dd3dae16bca797ae04698@rumz.com>\",\"In-Reply-To\":\"<fd5cc787053dd3dae16bca797ae04698@rumz.com>\",\"From\":\"sbaan da <sharebaanda@gmail.com>\",\"Date\":\"Mon, 17 Oct 2022 23:23:41 +0300\",\"Message-Id\":\"<CAEHgj6K_=c_39SVLdqWLEuYvhOuC8hvRWi-AGz7SFxVmQCgVsA@mail.gmail.com>\",\"Subject\":\"Re: test\",\"To\":\"support@rumz.com\",\"Content-Type\":\"multipart\\/alternative; boundary=\\\"00000000000006c96d05eb40c1e6\\\"\"},\"text\":\"check logs with reply\\n\\nOn Mon, Oct 17, 2022 at 11:22 PM <support@rumz.com> wrote:\\n\\n> Hi!\\n>\\n\\n\",\"text_flowed\":false,\"html\":\"<div dir=\\\"ltr\\\">check logs with reply<\\/div><br><div class=\\\"gmail_quote\\\"><div dir=\\\"ltr\\\" class=\\\"gmail_attr\\\">On Mon, Oct 17, 2022 at 11:22 PM &lt;<a href=\\\"mailto:support@rumz.com\\\">support@rumz.com<\\/a>&gt; wrote:<br><\\/div><blockquote class=\\\"gmail_quote\\\" style=\\\"margin:0px 0px 0px 0.8ex;border-left:1px solid rgb(204,204,204);padding-left:1ex\\\">Hi!<br>\\n<\\/blockquote><\\/div>\\n\\n\",\"from_email\":\"sharebaanda@gmail.com\",\"from_name\":\"sbaan da\",\"to\":[[\"support@rumz.com\",null]],\"subject\":\"Re: test\",\"spf\":{\"result\":\"pass\",\"detail\":\"sender SPF authorized\"},\"spam_report\":{\"score\":-3.8,\"matched_rules\":[{\"name\":\"FREEMAIL_FROM\",\"score\":0,\"description\":\"Sender email is commonly abused enduser mail\"},{\"name\":\"(sharebaanda[at]gmail.com)\",\"score\":0,\"description\":null},{\"name\":\"HTML_MESSAGE\",\"score\":0,\"description\":\"BODY: HTML included in message\"},{\"name\":\"DKIM_VALID_AU\",\"score\":-0.1,\"description\":\"Message has a valid DKIM or DK signature from\"},{\"name\":\"domain\",\"score\":0,\"description\":null},{\"name\":\"DKIM_SIGNED\",\"score\":0.1,\"description\":\"Message has a DKIM or DK signature, not necessarily\"},{\"name\":null,\"score\":0,\"description\":null},{\"name\":\"DKIM_VALID\",\"score\":-0.1,\"description\":\"Message has at least one valid DKIM or DK signature\"},{\"name\":\"URIBL_BLOCKED\",\"score\":0,\"description\":\"ADMINISTRATOR NOTICE: The query to URIBL was\"},{\"name\":\"See\",\"score\":0,\"description\":null},{\"name\":\"more\",\"score\":0,\"description\":\"information.\"},{\"name\":\"rumz.com]\",\"score\":0,\"description\":null},{\"name\":\"RCVD_IN_MSPIKE_H2\",\"score\":-0,\"description\":\"RBL: Average reputation (+2)\"},{\"name\":\"listed\",\"score\":0,\"description\":\"in list.dnswl.org]\"},{\"name\":\"RCVD_IN_DNSWL_HI\",\"score\":-5,\"description\":\"RBL: Sender listed at http:\\/\\/www.dnswl.org\\/,\"},{\"name\":\"trust\",\"score\":0,\"description\":null},{\"name\":\"RDNS_NONE\",\"score\":1.3,\"description\":\"Delivered to internal network by a host with no rDNS\"}]},\"dkim\":{\"signed\":true,\"valid\":true},\"email\":\"support@rumz.com\",\"tags\":[],\"sender\":null,\"template\":null}}]");

    $json = json_decode("[{\"event\":\"inbound\",\"ts\":1666036864,\"msg\":{\"raw_msg\":\"Received: from mail-oi1-f177.google.com (unknown [209.85.167.177])\\n\\tby relay-7.us-west-2.relay-prod (Postfix) with ESMTPS id 55D7425E01\\n\\tfor <support@rumz.com>; Mon, 17 Oct 2022 20:01:04 +0000 (UTC)\\nReceived: by mail-oi1-f177.google.com with SMTP id g130so13332082oia.13\\n        for <support@rumz.com>; Mon, 17 Oct 2022 13:01:04 -0700 (PDT)\\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed\\/relaxed;\\n        d=gmail.com; s=20210112;\\n        h=to:subject:message-id:date:from:mime-version:from:to:cc:subject\\n         :date:message-id:reply-to;\\n        bh=EA9f36SWBytuD035r94E9kUQyDvPJ5s\\/pMW4ZmlP4Ig=;\\n        b=QK\\/VkJgwwHD4nkW98NBMZgcLjWGsrJUnjQTtD\\/+Ynx4fp33VySkM6flSFd\\/ih5MI5B\\n         3l3GLzSF0VoneW58bZv4u9YWJL4pWJ\\/0v5ExKc60bod6RNgAKm9jdM7lNzQrOMaIJYzW\\n         87qdvFgB582kB4F5RhP62uw6kKmCgSrKYDrT9sxHQWTK5SvYOd571s4DMxRoPhs6rBCn\\n         PuGWviEcLwOUkYDdtDK2UX8AWaz9NCqGvJQCBQi8c07sBr6WjzbCagpW6kqPU0WEX1j0\\n         g3fLOJL6GNHzBAfVfucMSCulN06OTvqmB5WoCl9EXDlxC92yyCFxFG8ReU7aI15G+6M8\\n         S74Q==\\nX-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed\\/relaxed;\\n        d=1e100.net; s=20210112;\\n        h=to:subject:message-id:date:from:mime-version:x-gm-message-state\\n         :from:to:cc:subject:date:message-id:reply-to;\\n        bh=EA9f36SWBytuD035r94E9kUQyDvPJ5s\\/pMW4ZmlP4Ig=;\\n        b=BczpBAU9RQH3xUwSmj3LzZ\\/B3fOBRFkJsY+SJbuVA4uWTsNQ3beRKirdBHfvVou+Ct\\n         qAjwREy+bUBPa1ogzyHUcaoKbjU+ygE\\/5V2eKMj7PD8KaR2GO7PXmdm4Bldx8B0x7clr\\n         5nEWFEwK9S+P8gDj5CwkPY\\/1+vj\\/pZ8E27x27gt9\\/945hwMjeKif+xPv6zjjsOGXNMSU\\n         G2OsW1sbN4zBoB\\/b9vDV0wlnsRpnc2mdMQI1cq4+sxVcb8xb62pE5B\\/cDVjbY7ae3Mld\\n         HY72CXOKBQmaQM8U\\/0TLnsWagMym+43ynRTTId85O50B9uBPsUooWTMEGjnWDKiWS1zk\\n         Okvw==\\nX-Gm-Message-State: ACrzQf1B5u78AHBVVcGXh+5NOds2LCRSSn7JQhDonDLZYIrO+Rt7zYmI\\n\\t4uy1+PpDJxWZbxAo1U2QnpO13ae8jPDJI2AA72mPUO\\/x\\nX-Google-Smtp-Source: AMsMyM6GI+lBJYgJzOzS8kyRZRFH4RX\\/3wKIgv\\/o9i9U\\/YOPBXBbaWRkzCFBovVXsJ\\/f4qpl1iaj8DHkhf3fiCn3jQg=\\nX-Received: by 2002:a05:6808:200b:b0:355:2801:fe4a with SMTP id\\n q11-20020a056808200b00b003552801fe4amr5791246oiw.30.1666036862601; Mon, 17\\n Oct 2022 13:01:02 -0700 (PDT)\\nMIME-Version: 1.0\\nFrom: sbaan da <sharebaanda@gmail.com>\\nDate: Mon, 17 Oct 2022 23:00:51 +0300\\nMessage-ID: <CAEHgj6Kvh77221Ln9GeyA-ZF6a0C3AWydpwdXTwacF1dHbp1kw@mail.gmail.com>\\nSubject: test\\nTo: support@rumz.com\\nContent-Type: multipart\\/alternative; boundary=\\\"00000000000060cb3a05eb406f24\\\"\\n\\n--00000000000060cb3a05eb406f24\\nContent-Type: text\\/plain; charset=\\\"UTF-8\\\"\\n\\nhello!\\n\\n--00000000000060cb3a05eb406f24\\nContent-Type: text\\/html; charset=\\\"UTF-8\\\"\\n\\n<div dir=\\\"ltr\\\">hello!<\\/div>\\n\\n--00000000000060cb3a05eb406f24--\",\"headers\":{\"Received\":[\"from mail-oi1-f177.google.com (unknown [209.85.167.177]) by relay-7.us-west-2.relay-prod (Postfix) with ESMTPS id 55D7425E01 for <support@rumz.com>; Mon, 17 Oct 2022 20:01:04 +0000 (UTC)\",\"by mail-oi1-f177.google.com with SMTP id g130so13332082oia.13 for <support@rumz.com>; Mon, 17 Oct 2022 13:01:04 -0700 (PDT)\"],\"Dkim-Signature\":\"v=1; a=rsa-sha256; c=relaxed\\/relaxed; d=gmail.com; s=20210112; h=to:subject:message-id:date:from:mime-version:from:to:cc:subject :date:message-id:reply-to; bh=EA9f36SWBytuD035r94E9kUQyDvPJ5s\\/pMW4ZmlP4Ig=; b=QK\\/VkJgwwHD4nkW98NBMZgcLjWGsrJUnjQTtD\\/+Ynx4fp33VySkM6flSFd\\/ih5MI5B 3l3GLzSF0VoneW58bZv4u9YWJL4pWJ\\/0v5ExKc60bod6RNgAKm9jdM7lNzQrOMaIJYzW 87qdvFgB582kB4F5RhP62uw6kKmCgSrKYDrT9sxHQWTK5SvYOd571s4DMxRoPhs6rBCn PuGWviEcLwOUkYDdtDK2UX8AWaz9NCqGvJQCBQi8c07sBr6WjzbCagpW6kqPU0WEX1j0 g3fLOJL6GNHzBAfVfucMSCulN06OTvqmB5WoCl9EXDlxC92yyCFxFG8ReU7aI15G+6M8 S74Q==\",\"X-Google-Dkim-Signature\":\"v=1; a=rsa-sha256; c=relaxed\\/relaxed; d=1e100.net; s=20210112; h=to:subject:message-id:date:from:mime-version:x-gm-message-state :from:to:cc:subject:date:message-id:reply-to; bh=EA9f36SWBytuD035r94E9kUQyDvPJ5s\\/pMW4ZmlP4Ig=; b=BczpBAU9RQH3xUwSmj3LzZ\\/B3fOBRFkJsY+SJbuVA4uWTsNQ3beRKirdBHfvVou+Ct qAjwREy+bUBPa1ogzyHUcaoKbjU+ygE\\/5V2eKMj7PD8KaR2GO7PXmdm4Bldx8B0x7clr 5nEWFEwK9S+P8gDj5CwkPY\\/1+vj\\/pZ8E27x27gt9\\/945hwMjeKif+xPv6zjjsOGXNMSU G2OsW1sbN4zBoB\\/b9vDV0wlnsRpnc2mdMQI1cq4+sxVcb8xb62pE5B\\/cDVjbY7ae3Mld HY72CXOKBQmaQM8U\\/0TLnsWagMym+43ynRTTId85O50B9uBPsUooWTMEGjnWDKiWS1zk Okvw==\",\"X-Gm-Message-State\":\"ACrzQf1B5u78AHBVVcGXh+5NOds2LCRSSn7JQhDonDLZYIrO+Rt7zYmI 4uy1+PpDJxWZbxAo1U2QnpO13ae8jPDJI2AA72mPUO\\/x\",\"X-Google-Smtp-Source\":\"AMsMyM6GI+lBJYgJzOzS8kyRZRFH4RX\\/3wKIgv\\/o9i9U\\/YOPBXBbaWRkzCFBovVXsJ\\/f4qpl1iaj8DHkhf3fiCn3jQg=\",\"X-Received\":\"by 2002:a05:6808:200b:b0:355:2801:fe4a with SMTP id q11-20020a056808200b00b003552801fe4amr5791246oiw.30.1666036862601; Mon, 17 Oct 2022 13:01:02 -0700 (PDT)\",\"Mime-Version\":\"1.0\",\"From\":\"sbaan da <sharebaanda@gmail.com>\",\"Date\":\"Mon, 17 Oct 2022 23:00:51 +0300\",\"Message-Id\":\"<CAEHgj6Kvh77221Ln9GeyA-ZF6a0C3AWydpwdXTwacF1dHbp1kw@mail.gmail.com>\",\"Subject\":\"test\",\"To\":\"support@rumz.com\",\"Content-Type\":\"multipart\\/alternative; boundary=\\\"00000000000060cb3a05eb406f24\\\"\"},\"text\":\"hello!\\n\\n\",\"text_flowed\":false,\"html\":\"<div dir=\\\"ltr\\\">hello!<\\/div>\\n\\n\",\"from_email\":\"sharebaanda@gmail.com\",\"from_name\":\"sbaan da\",\"to\":[[\"support@rumz.com\",null]],\"subject\":\"test\",\"spf\":{\"result\":\"pass\",\"detail\":\"sender SPF authorized\"},\"spam_report\":{\"score\":1.2,\"matched_rules\":[{\"name\":\"RCVD_IN_DNSWL_NONE\",\"score\":-0,\"description\":\"RBL: Sender listed at http:\\/\\/www.dnswl.org\\/,\"},{\"name\":\"trust\",\"score\":0,\"description\":null},{\"name\":\"listed\",\"score\":0,\"description\":\"in wl.mailspike.net]\"},{\"name\":\"FREEMAIL_FROM\",\"score\":0,\"description\":\"Sender email is commonly abused enduser mail\"},{\"name\":\"(sharebaanda[at]gmail.com)\",\"score\":0,\"description\":null},{\"name\":\"HTML_MESSAGE\",\"score\":0,\"description\":\"BODY: HTML included in message\"},{\"name\":\"DKIM_VALID_AU\",\"score\":-0.1,\"description\":\"Message has a valid DKIM or DK signature from\"},{\"name\":\"domain\",\"score\":0,\"description\":null},{\"name\":\"DKIM_SIGNED\",\"score\":0.1,\"description\":\"Message has a DKIM or DK signature, not necessarily\"},{\"name\":null,\"score\":0,\"description\":null},{\"name\":\"DKIM_VALID\",\"score\":-0.1,\"description\":\"Message has at least one valid DKIM or DK signature\"},{\"name\":\"RCVD_IN_MSPIKE_H2\",\"score\":-0,\"description\":\"RBL: Average reputation (+2)\"},{\"name\":\"RDNS_NONE\",\"score\":1.3,\"description\":\"Delivered to internal network by a host with no rDNS\"}]},\"dkim\":{\"signed\":true,\"valid\":true},\"email\":\"support@rumz.com\",\"tags\":[],\"sender\":null,\"template\":null}}]");

    dd($json, $json2);
    die;
//    dd(config('services.mailchimp.key'));
//    $userId = User::where('id', '3e370e8d-4efb-4904-b561-665251247bfc')->first()->id;
    //user that belongs to rum > for policies
//    $userRum = Rum::whereHas('users', function (Builder $query) use($userId) {
//        $query->where('users.id', $userId)->where('users_rums.granted', 1);
//    })->where('type', Rum::TYPE_FREE)->get();
//    $userRum = Rum::with('posts')->whereHas('users')->get();
    // rum posts with number of likes, users who liked, number of comments and comments
//    $posts = $userRum->posts;

    //check valid address
//    $crawler = Goutte::request('GET', 'https://9gag.com/gag/ay9rmpV');
//    $r = $crawler->filterXpath("//meta[@property='og:title']")->extract(['content']);
//    var_dump($r);
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
