<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRumRequest;
use App\Models\HistoryPayment;
use App\Models\Rum;
use App\Models\RumHashtag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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

    public function store(StoreRumRequest $request)
    {
        // TODO: add hashtags
        Rum::create(
            Arr::add(
                $request->validated(),
                'user_id',
                auth()->user()->id
            )
        );

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

            // send notification info
        } else {
            $rum->joined()->create([
                'user_id' => auth()->user()->id
            ]);

            // send notification approval
        }

        return response()->noContent();
    }

}
