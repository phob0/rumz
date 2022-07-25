<?php

use App\Models\Rum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('has a private rum and request join an accept', function () {
    $creator = User::factory()->create();
    $subscriber = User::factory()->create();

    Sanctum::actingAs(
        $creator,
        ['*']
    );

    $data = [
        'title' => 'TestNewRumPrivate',
        'description' => 'A small description.',
        'type' => 'private',
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

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $subscriber,
        ['*']
    );

    $joinSubscriberResponse = $this->patch('api/rum/join/'.$rum->id.'/private');

    $joinSubscriberResponse->assertStatus(204);

    $rum = $rum->fresh();

    expect($rum->join_requests)->toHaveCount(1);

    $this->assertEquals($rum->join_requests[0]->user_id, $subscriber->id);
    $this->assertEquals($rum->join_requests[0]->rum_id, $rum->id);
    $this->assertEquals($rum->join_requests[0]->granted, 0);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $creator,
        ['*']
    );

    expect($creator->unreadNotifications)->toHaveCount(1);

    $this->assertEquals($creator->notifications[0]->data['subscriber']['id'], $subscriber->id);
    $this->assertEquals($creator->notifications[0]->data['rum']['id'], $rum->id);

    $rejectSubscriberResponse = $this->patch('api/rum/grant/'.$rum->id.'/'.$subscriber->id, [
        'granted' => 1
    ]);

    $rejectSubscriberResponse->assertStatus(204);

    $rum = $rum->fresh();
    $creator = $creator->refresh();

    expect($rum->joined)->toHaveCount(1);

    expect($rum->joined[0]->granted)->toEqual(1);

    expect($creator->unreadNotifications)->toHaveCount(0);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $subscriber,
        ['*']
    );

    expect($subscriber->unreadNotifications)->toHaveCount(1);

});
