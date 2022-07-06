<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRumRequest;
use App\Http\Requests\UpdateRumRequest;
use App\Models\Rum;
use App\Models\RumHashtag;
use App\Models\User;
use App\Notifications\RumApprovalSubscriber;
use App\Notifications\RumRejectionSubscriber;
use App\Notifications\RumSubscriptionApproval;
use App\Notifications\RumSubscriptionPaymentInfo;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class RumController extends Controller
{
    // TODO: uprade returns with specific resources
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(Rum::where('type', '!=', 'confidential')->get());
    }

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function view(Rum $rum): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', $rum);
        return JsonResource::collection($rum->posts);
    }

    public function store(StoreRumRequest $request): JsonResource
    {
        $hashtags = array_filter($request->validated()['hashtags'], 'strlen');
        $path = !is_null($request->file('image')) ? $request->file('image')->store('public/images/rums') : null;

        $data = $request->validated();
        $data['image'] = $path;

        $rum = Rum::create(
            Arr::add(
                Arr::except($data, 'hashtags'),
                'user_id',
                auth()->user()->id
            )
        );

        if(!empty($hashtags)) {
            collect($hashtags)->each(function($hashtag) use($rum) {
                $rum->hashtags()->create([
                    'hashtag' => $hashtag
                ]);
            });
        }

        return JsonResource::make($rum);
    }

    public function edit(Rum $rum): JsonResource
    {
        $this->authorize('edit', $rum);

        return JsonResource::make($rum);
    }

    public function update(UpdateRumRequest $request, Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('update', $rum);

        $hashtags = array_filter($request->validated()['hashtags'], 'strlen'); null;

        $data = $request->validated();

        if (Storage::disk('local')->exists('public/images/temp/'.$request->image)) {
            Storage::disk('local')->move('public/images/temp/'.$request->image, 'public/images/rums/'.$request->image);
        }

        $rum->update(
            Arr::except($data, 'hashtags')
        );

        $rum->hashtags()->delete();

        if(!empty($hashtags)) {
            collect($hashtags)->each(function($hashtag) use($rum) {
                $rum->hashtags()->create([
                    'hashtag' => $hashtag
                ]);
            });
        }

        return response()->noContent();
    }

    public function delete(Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('delete', $rum);

        $rum->delete();

        return response()->noContent();
    }

    public function hashtagSuggestions(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(RumHashtag::where('hashtag', 'like', $request->q.'%')->get());
    }

    public function join(Request $request, Rum $rum, $type = false): \Illuminate\Http\Response
    {
        $this->authorize('join', [$rum, $type]);

        if($type === 'paid') {
            DB::transaction(function() use($rum, $request) {
                $subscription = $rum->subscriptions()->updateOrCreate([
                    'user_id' => auth()->user()->id,
                ], [
                    'is_paid' => 1,
                    'expire_at' => Carbon::now()->addMonth(),
                    'amount' => $request->amount
                ]);

                $subscription->history_payments()->create([
                    'amount' => $request->amount
                ]);
            });

            $rum->master->notify(new RumSubscriptionPaymentInfo($rum, auth()->user()->name . ' payed $'.$request->amount.' membership to join your rum.'));
        } else {
            $rum->joined()->create([
                'user_id' => auth()->user()->id
            ]);

            $rum->master->notify(new RumSubscriptionApproval($rum, auth()->user(),auth()->user()->name . ' is waiting your approval.'));
        }

        return response()->noContent();
    }

    public function grant(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        if(!isset($request->granted)) {
            throw new HttpResponseException(
                response()->json(['error' => 'Granted value is missing.'], Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        $this->authorize('grant', [$rum, $user]);

        $rum->joined()->where('user_id', $user->id)->first()->update([
            'granted' => $request->granted
        ]);

        auth()->user()->notifications->filter(function($item) use($rum) {
            return $item->data['rum']['id'] === $rum->id;
        })->markAsRead();

        $user->notify(new RumApprovalSubscriber($rum, 'Your request to join has been approved'));

        return response()->noContent();
    }

    public function reject(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $rum->joined()->where('user_id', $user->id)->first()->delete();

        auth()->user()->unreadNotifications->filter(function($item) use($rum) {
            return $item->data['rum']['id'] === $rum->id;
        })->markAsRead();

        $user->notify(new RumRejectionSubscriber($rum, 'Your request to join has been rejected'));

        return response()->noContent();
    }

    public function image(Request $request): \Illuminate\Http\JsonResponse
    {
        $file = $request->file('image');
        $path = $file->store('public/images/temp');

        return response()->json([
            'path' => $path,
            'file_name' => $file->hashName()
        ]);
    }

}
