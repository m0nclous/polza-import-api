<?php

namespace App\Http\Controllers\Import;

use App\Services\ImportCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CacheController extends Controller
{
    public function set(): JsonResponse
    {
        ImportCacheService::set();
        return response()->json(['success' => true]);
    }

    public function get(): JsonResponse
    {
        return response()->json(ImportCacheService::get())->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public function forget(): JsonResponse
    {
        ImportCacheService::forget();
        return response()->json(['success' => true]);
    }
}
