<?php

namespace happycog\craftmcp\tools;

use craft\services\Elements;
use happycog\craftmcp\actions\FormatUser;
use happycog\craftmcp\actions\ResolveUser;

class DeleteUser
{
    public function __construct(
        protected Elements $elementsService,
        protected FormatUser $formatUser,
        protected ResolveUser $resolveUser,
    ) {
    }

    /**
     * Delete a Craft user by ID, email, or username.
     *
     * Provide exactly one identifier. By default this performs a soft delete; set `permanentlyDelete`
     * to true to remove the user permanently.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** User ID to delete. */
        ?int $userId = null,

        /** Resolve the user by exact email address. */
        ?string $email = null,

        /** Resolve the user by exact username. */
        ?string $username = null,

        /** Permanently delete instead of soft deleting. */
        bool $permanentlyDelete = false,
    ): array {
        $user = ($this->resolveUser)($userId, $email, $username);

        $response = [
            '_notes' => 'The user was successfully deleted.',
            ...($this->formatUser)($user),
            'deletedPermanently' => $permanentlyDelete,
        ];

        throw_unless($this->elementsService->deleteElement($user, $permanentlyDelete), 'Failed to delete user.');

        return $response;
    }
}
