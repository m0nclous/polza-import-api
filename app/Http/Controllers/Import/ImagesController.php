<?php

namespace App\Http\Controllers\Import;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class ImagesController extends Controller
{
    public function productsSync(Request $request)
    {
        $cursor = $request->get('cursor');
        $limit = (int) $request->get('limit', config('filesystems.upload_per_request'));
        $storage = Storage::disk('ftp');
        $directories = $storage->directories('/img/');

        $current = 0;
        foreach ($directories as $directory) {
            if ($current === $limit) break;
            if ($cursor && $cursor !== $directory) continue;
            $cursor = null;

            $files = $storage->files($directory);

            foreach ($files as $filename) {
                Storage::disk('wordpress-upload')->put($filename, $storage->get($filename));
            }

            $current++;
        }

        return response()->json(['cursor' => $current === 0 ? null : ($directory ?? null)])->setEncodingOptions(JSON_UNESCAPED_SLASHES);
    }
}
