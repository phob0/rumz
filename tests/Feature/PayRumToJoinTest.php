<?php

it('has payrumtojoin page', function () {
    $response = $this->get('/payrumtojoin');

    $response->assertStatus(200);
});
