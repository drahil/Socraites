<?php

namespace drahil\Socraites\Services;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CacheService
{
    public function __construct(
        private readonly CacheInterface $cache
    ) {}

    /**
     * Retrieve a value from the cache or resolve it using the provided callable.
     *
     * @param string $key The cache key.
     * @param callable $resolver A callable that returns the value to cache if not found.
     * @param int $ttl Time to live for the cached value in seconds.
     * @return mixed The cached value or the resolved value.
     * @throws InvalidArgumentException
     */
    public function getFromCache(string $key, callable $resolver, int $ttl = 3600): mixed
    {
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $value = $resolver();
        $this->cache->set($key, $value, $ttl);

        return $value;
    }
}