<?php

namespace App\Http\Controllers\Import;

use App\Services\ImportCacheImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CacheImageController extends Controller
{
    public function set(): JsonResponse
    {
        ImportCacheImageService::set();
        return response()->json(['success' => true]);
    }

    public function get(): JsonResponse
    {
        return response()->json(ImportCacheImageService::get())->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public function forget(): JsonResponse
    {
        ImportCacheImageService::forget();
        return response()->json(['success' => true]);
    }
}
