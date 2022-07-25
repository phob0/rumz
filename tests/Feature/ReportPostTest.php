<?php

use App\Models\Rum;
use App\Models\RumPost;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('has report post', function () {
    $creator = User::factory()->create();
    $subscriber = User::factory()->create();
    $second_subscriber = User::factory()->create();

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
    $this->patch('api/rum/member/invite/' . $rum->id . '/' . $second_subscriber->id);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $subscriber,
        ['*']
    );

    $this->patch('api/rum/member/accept-invite/' . $rum->id);

    $payload = [
        'rum_id' => $rum->id,
        'title' => 'New post',
        'description' => 'asdasdasd',
        'metadata' => [
            'site_name' => 'CNN',
            'title' => 'Cheney says January 6 committee could make multiple criminal referrals, including of Trump',
            'description' => 'The House select committee investigating the January 6, 2021, insurrection could make multiple criminal referrals, including of former President Donald Trump, the panel`s vice chair, Rep. Liz Cheney, said in an interview broadcast Sunday.',
            'image' => 'https://cdn.cnn.com/cnnnext/dam/assets/220629165703-liz-cheney-file-super-tease.jpg',
            'video' => '',
        ],
    ];

    $newPostResponse = $this->post('api/rum/post/create', $payload);

    $newPostResponse->assertStatus(201);

    expect($createRumResponse->content())->toBeJson();

    $post = RumPost::find(json_decode($newPostResponse->content())->data->id);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $second_subscriber,
        ['*']
    );

    $this->patch('api/rum/member/accept-invite/' . $rum->id);

    $reportRumPostResponse = $this->patch('api/rum/post/report/' . $post->id);

    $reportRumPostResponse->assertStatus(204);

    auth()->user()->tokens()->delete();

    Sanctum::actingAs(
        $creator,
        ['*']
    );

    expect($creator->unreadNotifications->where('type', \App\Notifications\PostReport::class))->toHaveCount(1);

});
