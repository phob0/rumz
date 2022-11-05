<?php

use App\Models\Rum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('has ban unban', function () {
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

    $this->patch('api/rum/member/invite/accept/' . $rum->id);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $creator,
        ['*']
    );

    $banResponse = $this->patch('api/rum/member/ban-unban/ban/' . $rum->id . '/' . $subscriber->id);

    $banResponse->assertStatus(204);

    expect($subscriber->unreadNotifications)->toHaveCount(1);

    $rum = $rum->refresh();

    expect($rum->refresh()->join_requests)->toHaveCount(1);

    $unbanResponse = $this->patch('api/rum/member/ban-unban/unban/' . $rum->id . '/' . $subscriber->id);

    $unbanResponse->assertStatus(204);

    expect($subscriber->unreadNotifications)->toHaveCount(1);

    $rum = $rum->refresh();

    expect($rum->refresh()->joined)->toHaveCount(1);

});
