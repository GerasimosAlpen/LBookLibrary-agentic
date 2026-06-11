<?php

it('returns a successful response for the login page', function () {
    $response = $this->get('/auth/login');

    $response->assertStatus(200);
});
