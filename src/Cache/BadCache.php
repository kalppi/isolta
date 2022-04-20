<?php

namespace MegaCorp\Cache;

final class BadCache implements ICache
{
    private array $cache = [];

    public function get(string $name)
    {
        return $this->cache[$name] ?? null;
    }

    public function set(string $name, $value)
    {
        $this->cache[$name] = $value;
    }
}
