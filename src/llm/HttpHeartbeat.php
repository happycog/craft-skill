<?php

declare(strict_types=1);

namespace happycog\craftmcp\llm;

/**
 * Emits `['type' => 'heartbeat']` events at most once every `intervalSeconds`,
 * intended to be wired into Guzzle's `progress` option (which in turn maps to
 * cURL's XFERINFOFUNCTION) so the SSE connection stays alive while the driver
 * is blocked waiting on a slow upstream.
 *
 * The time source is injectable so unit tests can step "time" forward without
 * actually sleeping.
 */
final class HttpHeartbeat
{
    private float $lastEmit;

    /** @var callable(): float */
    private $clock;

    /**
     * @param float $intervalSeconds minimum seconds between heartbeats
     * @param ?callable(): float $clock returns current time as float seconds; defaults to microtime(true)
     */
    public function __construct(
        private readonly float $intervalSeconds = 5.0,
        ?callable $clock = null,
    ) {
        $this->clock    = $clock ?? static fn (): float => microtime(true);
        $this->lastEmit = ($this->clock)();
    }

    /**
     * Called from a Guzzle progress callback. Emits a heartbeat event if
     * enough time has passed since the last one.
     *
     * @param callable(array<string, mixed>): void $onEvent
     */
    public function tick(callable $onEvent): void
    {
        $now = ($this->clock)();
        if ($now - $this->lastEmit >= $this->intervalSeconds) {
            $this->lastEmit = $now;
            $onEvent(['type' => 'heartbeat']);
        }
    }
}
