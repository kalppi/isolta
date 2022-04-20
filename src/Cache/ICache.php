<?php

namespace MegaCorp\Cache;

/**
 * TODO: use PSR compatible cache
 */

interface ICache
{
    public function get(string $name);
    public function set(string $name, $value);
}
