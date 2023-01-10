<?php

namespace App\Services;

use App\Models\Wp\Post;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportImageService extends AbstractFile1CService
{
    static function parseImages(): array
    {
        $posts = Post::typeAttachment()->get(['ID', 'guid'])->keyBy('guid');

        return array_reduce(Storage::disk('wordpress-upload')->allFiles('/1c/img'), function ($carry, $item) use ($posts) {
            $pathInfo = pathinfo($item);

            if (!Str::isUuid($guid = $pathInfo['filename'])) {
                return $carry;
            }

            $carry[$guid] = [
                'postGuid' => array_values(array_slice(explode('/', $pathInfo['dirname']), -1))[0],
                'file' => $item,
            ];

            $post = $posts->get($guid);
            if ($post) {
                $carry[$guid]['ID'] = $post->ID;
            }

            return $carry;
        }, []);
    }
}
