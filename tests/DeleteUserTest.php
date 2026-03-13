<?php

use craft\elements\User;
use happycog\craftmcp\tools\DeleteUser;

beforeEach(function () {
    $this->tool = Craft::$container->get(DeleteUser::class);
});

it('soft deletes a user by email', function () {
    $user = createTestUser();

    $response = $this->tool->__invoke(email: $user->email);

    expect($response['id'])->toBe($user->id);

    $deletedUser = Craft::$app->getElements()->getElementById($user->id, User::class, null, ['status' => null]);
    expect($deletedUser)->toBeNull();
});
