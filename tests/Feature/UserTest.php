<?php

it('has user page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
