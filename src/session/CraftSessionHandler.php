<?php

declare(strict_types=1);

namespace happycog\craftmcp\session;

use Craft;
use craft\helpers\StringHelper;
use PhpMcp\Server\Contracts\SessionHandlerInterface;
use yii\caching\CacheInterface;

class CraftSessionHandler implements SessionHandlerInterface
{
    /**
     * The cache component instance.
     */
    protected CacheInterface $cache;

    /**
     * The number of seconds the session should be valid.
     */
    protected int $ttl;

    /**
     * The cache key prefix for sessions.
     */
    protected string $prefix;

    /**
     * Create a new Craft session handler instance.
     */
    public function __construct(CacheInterface $cache = null, int $ttl = 3600, string $prefix = 'mcp_session')
    {
        $this->cache = $cache ?? Craft::$app->getCache();
        $this->ttl = $ttl;
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $sessionId): string|false
    {
        $cacheKey = $this->getCacheKey($sessionId);
        $data = $this->cache->get($cacheKey);
        
        if ($data === false) {
            return false;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $sessionId, string $data): bool
    {
        $cacheKey = $this->getCacheKey($sessionId);
        return $this->cache->set($cacheKey, $data, $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $sessionId): bool
    {
        $cacheKey = $this->getCacheKey($sessionId);
        return $this->cache->delete($cacheKey);
    }

    /**
     * {@inheritdoc}
     */
    public function gc(int $maxLifetime): array
    {
        // Cache components handle their own garbage collection
        // We can't easily identify which keys to delete without
        // maintaining a separate index, so we return an empty array
        // The cache will handle expiration based on TTL
        return [];
    }

    /**
     * Get the cache key for a session ID.
     */
    protected function getCacheKey(string $sessionId): string
    {
        return $this->prefix . ':' . $sessionId;
    }

    /**
     * Generate a unique session ID.
     */
    public function generateSessionId(): string
    {
        return 'craft_mcp_' . StringHelper::randomString(32) . '_' . time();
    }
}