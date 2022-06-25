<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRumRequest;
use App\Models\HistoryPayment;
use App\Models\Rum;
use App\Models\RumHashtag;
use App\Models\User;
use App\Notifications\RumSubscriptionApproval;
use App\Notifications\RumSubscriptionPaymentInfo;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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

    public function store(StoreRumRequest $request): \Illuminate\Http\Response
    {
        $hashtags = array_filter($request->validated()['hashtags'], 'strlen');
        $path = $request->file('image')->store('public/images/rums');

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

        return response()->noContent();
    }

    public function edit(Rum $rum): JsonResource
    {
        $this->authorize('edit', $rum);

        return JsonResource::make($rum);
    }

    public function update(StoreRumRequest $request, Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('update', $rum);

        $rum->update($request->validated());

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

            $rum->master->notify(new RumSubscriptionPaymentInfo($rum->without('master')->first(), auth()->user(), auth()->user()->name . ' payed $'.$request->amount.' membership to join your rum.'));
        } else {
            $rum->joined()->create([
                'user_id' => auth()->user()->id
            ]);

            $rum->master->notify(new RumSubscriptionApproval($rum->without('master')->first(), auth()->user(), auth()->user()->name . ' is waiting your approval.'));
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

        return response()->noContent();
    }

}
