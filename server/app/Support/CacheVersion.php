<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheVersion
{
    public static function get(string $name): int
    {
        return Cache::rememberForever("ver:$name", function () {
            return 1;
        });
    }

    public static function bump(string $name): void
    {
        $current = self::get($name);
        Cache::forever("ver:$name", $current + 1);
    }
}

