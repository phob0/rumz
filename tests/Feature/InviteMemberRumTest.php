<?php

use App\Models\Rum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('sends invite member rum', function () {
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

    $createRumResponse->assertStatus(201);

    expect($createRumResponse->content())->toBeJson();

    $rum = Rum::find(json_decode($createRumResponse->content())->data->id);

    $this->assertNotNull($rum);

    $this->assertEquals($rum->title, $data['title']);

    $this->assertEquals($rum->user_id, $creator->id);

    $inviteUserRumResponse = $this->patch('api/rum/member/invite/' . $rum->id . '/' . $subscriber->id);

    $inviteUserRumResponse->assertStatus(204);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $subscriber,
        ['*']
    );

    expect($subscriber->unreadNotifications)->toHaveCount(1);

});
