<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRumRequest;
use App\Models\Rum;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RumController extends Controller
{
    // TODO: uprade returns with resources
    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Rum::where('type', '!=', 'confidential')->get());
    }

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function view(Rum $rum): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $rum);
        return response()->json(['rum' => $rum->posts]);
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

    public function edit(Rum $rum): \Illuminate\Http\JsonResponse
    {
        $this->authorize('edit', $rum);

        return response()->json(['rum' => $rum]);
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

}
