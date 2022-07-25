<?php

use App\Models\Rum;
use App\Models\User;
use App\Notifications\RemoveMember;
use Laravel\Sanctum\Sanctum;

it('has remove member', function () {
    $creator = User::factory()->create();
    $subscriber = User::factory()->create();

    Sanctum::actingAs(
        $creator,
        ['*']
    );

    $data = [
        'title' => 'TestNewRumConfidential',
        'description' => 'A small description.',
        'type' => 'confidential',
        'privilege' => 'all',
        'hashtags' => [],
    ];

    $createRumResponse = $this->post('api/rum/create', $data);

    $rum = Rum::find(json_decode($createRumResponse->content())->data->id);

    $this->patch('api/rum/member/invite/' . $rum->id . '/' . $subscriber->id);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $subscriber,
        ['*']
    );

    $this->patch('api/rum/member/accept-invite/' . $rum->id);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $creator,
        ['*']
    );

    $removeMemberResponse = $this->patch('api/rum/member/remove/' . $rum->id . '/' . $subscriber->id);

    $removeMemberResponse->assertStatus(204);

    $rum = $rum->refresh();

    expect($rum->users)->toHaveCount(0);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $subscriber,
        ['*']
    );

    expect($subscriber->notifications->where('type', RemoveMember::class))->toHaveCount(1);

    //TODO: small unread notification bug | temp query
});
