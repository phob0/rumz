<?php

use App\Models\Rum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('has report rum', function () {
    $superadmin = User::factory()->create(['superadmin' => 1]);
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

    $reportRumResponse = $this->patch('api/rum/report/' . $rum->id);

    $reportRumResponse->assertStatus(204);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $superadmin,
        ['*']
    );

    expect($superadmin->unreadNotifications)->toHaveCount(1);
});
