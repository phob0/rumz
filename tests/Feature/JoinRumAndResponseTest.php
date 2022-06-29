<?php

use App\Models\Rum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('has a private rum', function () {
    $creator = User::factory()->create();

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

    $response = $this->post('api/rum/create', $data);

    $response->assertStatus(201);

    expect($response->content())->toBeJson();

    $rum = Rum::find(json_decode($response->content())->data->id);

    $this->assertNotNull($rum);

    $this->assertEquals($rum->title, $data['title']);

    $this->assertEquals($rum->user_id, $creator->id);

});

