<?php

use yii\web\ForbiddenHttpException;

test('chat stream requires a logged in user', function () {
    Craft::$app->getUser()->logout(false);

    $this->expectException(ForbiddenHttpException::class);

    $this->postJson('actions/skills/chat/stream', [
        'message' => 'Hello',
        'messages' => [],
    ]);
});

test('chat stream requires control panel access', function () {
    $user = $this->createMock(\craft\elements\User::class);
    $user->method('can')->with('accessCp')->willReturn(false);
    Craft::$app->getUser()->setIdentity($user);

    $this->expectException(ForbiddenHttpException::class);

    $this->postJson('actions/skills/chat/stream', [
        'message' => 'Hello',
        'messages' => [],
    ]);
});
