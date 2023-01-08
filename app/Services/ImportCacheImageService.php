<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ImportCacheImageService extends AbstractFile1CService
{
    protected const KEY = 'ImportCacheImageService';

    static public function set(): void
    {
        Cache::set(self::KEY, ImportImageService::parseImages(), now()->addHour());
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
