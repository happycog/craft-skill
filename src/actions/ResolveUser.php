<?php

namespace happycog\craftmcp\actions;

use craft\elements\User;

class ResolveUser
{
    public function __construct(
        protected \craft\services\Users $usersService,
    ) {
    }

    public function __invoke(?int $userId = null, ?string $email = null, ?string $username = null): User
    {
        $provided = array_filter([
            'userId' => $userId !== null,
            'email' => $email !== null,
            'username' => $username !== null,
        ]);

        throw_unless(count($provided) === 1, \InvalidArgumentException::class, 'Provide exactly one of userId, email, or username.');

        if ($userId !== null) {
            $user = $this->usersService->getUserById($userId);
            throw_unless($user instanceof User, \InvalidArgumentException::class, "User with ID {$userId} not found.");
            return $user;
        }

        if ($email !== null) {
            /** @var User|null $user */
            $user = User::find()->email($email)->status(null)->one();
            throw_unless($user instanceof User, \InvalidArgumentException::class, "User with email '{$email}' not found.");
            return $user;
        }

        /** @var User|null $user */
        $user = User::find()->username($username)->status(null)->one();
        throw_unless($user instanceof User, \InvalidArgumentException::class, "User with username '{$username}' not found.");

        return $user;
    }
}
