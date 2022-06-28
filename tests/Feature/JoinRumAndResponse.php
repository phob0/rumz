<?php

it('has joinrumandresponse page', function () {
    $response = $this->get('/joinrumandresponse');

    $response->assertStatus(200);
});
