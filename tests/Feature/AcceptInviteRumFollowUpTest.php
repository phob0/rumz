<?php

use App\Models\Rum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('has accept invite rum follow-up', function () {
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

    $acceptInviteResponse = $this->patch('api/rum/member/accept-invite/' . $rum->id);

    $acceptInviteResponse->assertStatus(204);

    expect($creator->unreadNotifications)->toHaveCount(1);
});
