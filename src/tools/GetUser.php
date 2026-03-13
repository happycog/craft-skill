<?php

namespace happycog\craftmcp\tools;

use happycog\craftmcp\actions\FormatUser;
use happycog\craftmcp\actions\ResolveUser;

class GetUser
{
    public function __construct(
        protected FormatUser $formatUser,
        protected ResolveUser $resolveUser,
    ) {
    }

    /**
     * Retrieve a single Craft user by ID, email, or username.
     *
     * Provide exactly one identifier. The response includes native user attributes, group membership,
     * effective permissions, direct permissions, and custom field values.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** User ID to look up. */
        ?int $userId = null,

        /** Resolve the user by exact email address. */
        ?string $email = null,

        /** Resolve the user by exact username. */
        ?string $username = null,
    ): array {
        $user = ($this->resolveUser)($userId, $email, $username);

        return [
            '_notes' => 'Retrieved user details.',
            ...($this->formatUser)($user),
        ];
    }
}
