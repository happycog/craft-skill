<?php

declare(strict_types=1);

namespace happycog\craftmcp\tools;

final class OpenUrl
{
    /**
     * Request that the embedded chat widget open a URL for the user.
     *
     * This is a chat-widget-only tool. It does not modify Craft content or
     * server state. The frontend watches for this tool call and decides whether
     * it is safe and appropriate to navigate the current browser surface.
     *
     * Use this after a successful content or configuration change when opening
     * the relevant result on the current surface will help the user immediately
     * review what changed.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** The destination URL to open for the user. */
        string $url,

        /** Optional short explanation for why the URL should be opened. */
        ?string $reason = null,
    ): array
    {
        $url = trim($url);

        if ($url === '') {
            throw new \InvalidArgumentException('The URL cannot be empty.');
        }

        return [
            '_notes' => 'The chat widget should open this URL if it is valid for the current surface.',
            'url' => $url,
            'reason' => $reason,
        ];
    }
}
