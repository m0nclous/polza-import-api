<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ImportCacheService extends AbstractFile1CService
{
    protected const KEY = 'ImportCacheService';

    static public function set(): void
    {
        Cache::set(self::KEY, ImportService::parseProducts(), now()->addHour());
    }

    static public function get(): array
    {
        return Cache::get(self::KEY, []);
    }

    static public function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
