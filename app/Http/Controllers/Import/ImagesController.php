<?php

namespace App\Http\Controllers\Import;

use App\Models\Wp\Post;
use FTP\Connection;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class ImagesController extends Controller
{
    public function productsSync(Request $request)
    {
        $cursor = $request->get('cursor');
        $limit = $request->get('limit', 10);
        $storage = Storage::disk('ftp');
        $directories = $storage->directories('/img/');

        $current = 0;
        foreach ($directories as $directory) {
            if ($current === $limit) break;
            if ($cursor && $cursor !== $directory) continue;
            $cursor = null;

            $files = $storage->files($directory);

            foreach ($files as $filename) {
                $filenameParts = explode('/', $filename);
                Storage::disk('wordpress')->put(end($filenameParts), $storage->get($filename));
            }

            $current++;
        }

        return response()->json(['cursor' => $current === 0 ? null : ($directory ?? null)])->setEncodingOptions(JSON_UNESCAPED_SLASHES);
    }
}
