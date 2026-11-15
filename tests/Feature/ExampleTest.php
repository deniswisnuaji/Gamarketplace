<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect('/gm/main.html');
});
