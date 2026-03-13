<?php

use happycog\craftmcp\tools\GetUser;

beforeEach(function () {
    $this->tool = Craft::$container->get(GetUser::class);
});

it('gets a user by id', function () {
    $user = createTestUser();

    $response = $this->tool->__invoke(userId: $user->id);

    expect($response['id'])->toBe($user->id);
});

it('gets a user by email', function () {
    $user = createTestUser();

    $response = $this->tool->__invoke(email: $user->email);

    expect($response['email'])->toBe($user->email);
});
