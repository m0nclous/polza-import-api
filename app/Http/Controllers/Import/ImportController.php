<?php

namespace App\Http\Controllers\Import;

use App\Models\Wp\Post;
use App\Models\Wp\PostMeta;
use App\Models\Wp\Term;
use App\Models\Wp\TermMeta;
use App\Models\Wp\TermRelationships;
use App\Models\Wp\TermTaxonomy;
use App\Services\Import1CService;
use App\Services\ImportCacheImageService;
use App\Services\ImportCacheService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function groupsSync(Import1CService $import1CService): array
    {
        // Берём группы товаров из XML
        $groups = $import1CService->getGroupsFromXml();

        // Добавляем к каждой группе её ID из базы, если есть
        TermMeta::ofInGuids(array_keys($groups))->get(['term_id', 'meta_value'])
            ->each(function ($termMeta) use (&$groups) {
                $groups[$termMeta->meta_value]['id'] = $termMeta->term_id;
            });

        // Импортируем только те группы, которых нет в БД
        foreach ($groups as $guid => &$group) {
            if (isset($group['id'])) {
                continue;
            }

            $term = Term::create(['name' => $group['name']]);
            $termMetaGuid = TermMeta::make(['meta_key' => TermMeta::META_KEY_GUID, 'meta_value' => $guid]);
            $termTaxonomyCategory = TermTaxonomy::make(
                ['taxonomy' => 'product_cat', 'parent' => isset($group['parent']) ? $groups[$group['parent']]['id'] : 0]
            );

            $term->meta()->save($termMetaGuid);
            $term->taxonomies()->save($termTaxonomyCategory);

            $group['id'] = $term->term_id;
        }

        $import1CService->setSyncGroupsCache($groups);

        return ['count' => count($groups)];
    }

    public function productsSync(Request $request)
    {
        $cursor = $request->input('cursor', 1);
        $_productsCache = ImportCacheService::get();
        $productsCacheChunks = array_chunk($_productsCache, 1000, true);
        $productsCache = $productsCacheChunks[$cursor - 1] ?? [];

        $postDefaultAttributes = (new Post)->getAttributes();
        $attributesMeta = [];

        $simpleProductTermTaxonomy = TermTaxonomy::simpleProduct()->first();
        $variationProductTermTaxonomy = TermTaxonomy::variableProduct()->first();

        $productsSimpleAttributes = [];
        $productsVariationAttributes = [];
        foreach ($productsCache as $guid => $productsItem) {
            $attributes = array_merge($postDefaultAttributes, [
                'post_title' => $productsItem['name'],
                'post_name' => Str::slug($productsItem['name']),
                'post_content' => $productsItem['content'] ?? '',
                'post_type' => isset($productsItem['parent']) ? 'product_variation' : 'product',
                'post_parent' => $productsItem['parent'] ?? 0,
                'guid' => $guid,
            ]);

            if ($attributes['post_type'] === 'product') {
                $productsSimpleAttributes[] = $attributes;
            } else {
                $productsVariationAttributes[] = $attributes;
            }
        }

        foreach (array_chunk($productsSimpleAttributes, 1000) as $chunkProductModels) {
            Post::upsert($chunkProductModels, ['guid'], [
                'post_title',
                'post_name',
                'post_content',
                'post_type',
                'post_parent',
                'post_modified',
                'post_modified_gmt'
            ]);
        }

        $simpleProductPosts = Post::typeProduct()->get(['ID', 'guid'])->keyBy('guid');
        foreach ($productsVariationAttributes as &$attributes) {
            $attributes['post_parent'] = $simpleProductPosts->get($attributes['post_parent'])->ID;
        }

        foreach (array_chunk($productsVariationAttributes, 1000) as $chunkProductModels) {
            Post::upsert($chunkProductModels, ['guid'], [
                'post_title',
                'post_name',
                'post_content',
                'post_type',
                'post_parent',
                'post_modified',
                'post_modified_gmt'
            ]);
        }

        $termRelationshipModels = [];

        $termsDatabase = Term::all();
        $termTaxonomiesDatabase = TermTaxonomy::all();
        foreach ($productsCache as $guid => $productsItem) {
            foreach ($productsItem['taxonomy'] ?? [] as $taxonomyKey => $productTerms) {
                foreach ($productTerms as $term) {
                    if (!$termsDatabase->where('slug', $term->slug)->count()) {
                        $term->save();
                        $termsDatabase->push($term);
                    } else {
                        $term = $termsDatabase->where('slug', $term->slug)->first();
                    }

                    $termTaxonomy = $termTaxonomiesDatabase->where('taxonomy', 'pa_' . $taxonomyKey)->where(
                        'term_id',
                        $term->term_id
                    )->first();

                    if (!$termTaxonomy) {
                        $termTaxonomy = TermTaxonomy::create(
                            ['taxonomy' => 'pa_' . $taxonomyKey, 'term_id' => $term->term_id]
                        );

                        $termTaxonomiesDatabase->push($termTaxonomy);
                    }

                    $termRelationshipModels[] = ['object_id' => $simpleProductPosts->get($guid)->ID, 'term_taxonomy_id' => $termTaxonomy->term_taxonomy_id];
                }
            }
        }

        foreach (Post::typeProduct()->orWhere(fn($query) => $query->typeVariation())->get() as $post) {
            $productCache = $productsCache[$post->guid] ?? null;
            if (!$productCache) {
                continue;
            }

            foreach ($productCache['meta'] ?? [] as $key => $value) {
                $attributesMeta[] = ['post_id' => $post->ID, 'meta_key' => $key, 'meta_value' => $value];
            }

            if (isset($productCache['group'])) {
                $termRelationshipModels[] = ['object_id' => $post->ID, 'term_taxonomy_id' => $productCache['group']];
            }

            if ($productCache['type'] !== 'variation') {
                $termRelationshipModels[] = [
                    'object_id' => $post->ID,
                    'term_taxonomy_id' => $productCache['type'] === 'simple'
                        ? $simpleProductTermTaxonomy->term_taxonomy_id
                        : $variationProductTermTaxonomy->term_taxonomy_id
                ];
            }
        }

        foreach (array_chunk($attributesMeta, 1000) as $attributesMetaChunk) {
            PostMeta::upsert($attributesMetaChunk, ['guid'], ['meta_value']);
        }

        foreach (array_chunk($termRelationshipModels, 1000) as $chunkTermRelationshipModels) {
            TermRelationships::insertOrIgnore($chunkTermRelationshipModels);
        }

        return ['count' => count($productsCache)];
    }

    public function imageInsert()
    {
        $postDefaultAttributes = (new Post)->getAttributes();
        $imagesByGuid = array_filter(ImportCacheImageService::get(), fn($item) => !isset($item['ID']));
        $posts = Post::whereIn('guid', array_map(fn($item) => $item['postGuid'], $imagesByGuid))->get(['ID', 'guid']
        )->keyBy('guid');

        $attributes = [];
        $metaAttributes = [];
        $meta = [];

        foreach ($imagesByGuid as $guid => $image) {
            if (!$post = $posts->get($image['postGuid'])) {
                continue;
            }

            $attributes[] = array_merge($postDefaultAttributes, [
                'post_title' => $image['postGuid'],
                'post_name' => $image['postGuid'],
                'post_parent' => $post->ID,
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'post_mime_type' => Storage::disk('wordpress-upload')->mimeType($image['file']),
                'guid' => $guid,
            ]);

            $meta[$guid] = [
                '_wp_attached_file' => $image['file'],
                '_wp_attachment_metadata' => serialize(['file' => $image['file']]),
            ];
        }

        foreach (array_chunk($attributes, 1000) as $attributesChunk) {
            Post::insert($attributesChunk);

            $attachmentPostGroups = Post::whereIn('guid', array_map(fn($item) => $item['guid'], $attributesChunk))
                ->get(['ID', 'guid', 'post_parent'])
                ->groupBy('post_parent');

            foreach ($attachmentPostGroups as $postParent => $postGroup) {
                $gallery = $postGroup->map->ID->toArray();
                $thumbnailId = $gallery[0];
                $gallery = array_diff($gallery, [$thumbnailId]);

                $metaAttributes[] = [
                    'post_id' => $postParent,
                    'meta_key' => '_thumbnail_id',
                    'meta_value' => $thumbnailId
                ];

                if ($gallery) {
                    $metaAttributes[] = [
                        'post_id' => $postParent,
                        'meta_key' => '_product_image_gallery',
                        'meta_value' => implode(',', $gallery)
                    ];
                }

                foreach ($postGroup as $post) {
                    foreach ($meta[$post->guid] ?? [] as $key => $value) {
                        $metaAttributes[] = ['post_id' => $post->ID, 'meta_key' => $key, 'meta_value' => $value];
                    }
                }
            }
        }

        foreach (array_chunk($metaAttributes, 1000) as $metaAttributesChunk) {
            PostMeta::upsert($metaAttributesChunk, ['meta_key', 'meta_value'], ['meta_value']);
        }

        return response()->json(['success' => true]);
    }
}
