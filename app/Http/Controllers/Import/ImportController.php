<?php

namespace App\Http\Controllers\Import;

use App\Models\Wp\Post;
use App\Models\Wp\PostMeta;
use App\Models\Wp\Term;
use App\Models\Wp\TermMeta;
use App\Models\Wp\TermRelationships;
use App\Models\Wp\TermTaxonomy;
use App\Services\Import1CService;
use App\Services\ImportCacheService;
use Illuminate\Routing\Controller;
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

            $term = Term::create(['name' => $group['name'], 'slug' => Str::slug($group['name'])]);
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

    public function productsSync()
    {
        $productsCache = ImportCacheService::get();

        $simpleProductTermTaxonomy = TermTaxonomy::simpleProduct()->first();
        $variationProductTermTaxonomy = TermTaxonomy::variableProduct()->first();

        $productDefaultAttributes = (new Post)->getAttributes();

        $productsSimpleAttributes = [];
        $productsVariationAttributes = [];
        foreach ($productsCache as $guid => $productsItem) {
            $attributes = array_merge($productDefaultAttributes, [
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

        $metaModels = [];
        $termRelationshipModels = [];

        foreach (Post::typeProduct()->orWhere(fn ($query) => $query->typeVariation())->get() as $post) {
            $productCache = $productsCache[$post->guid] ?? null;
            if (!$productCache) continue;

            foreach ($productCache['meta'] ?? [] as $key => $value) {
                $metaModels[] = ['post_id' => $post->ID, 'meta_key' => $key, 'meta_value' => $value];
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

        foreach (array_chunk($metaModels, 1000) as $chunkMetaModels) {
            PostMeta::upsert($chunkMetaModels, ['guid'], ['meta_value',]);
        }

        foreach (array_chunk($termRelationshipModels, 1000) as $chunkTermRelationshipModels) {
            TermRelationships::upsert($chunkTermRelationshipModels, ['object_id', 'term_taxonomy_id'], ['term_taxonomy_id']);
        }

        return ['count' => count($productsCache)];
    }
}
