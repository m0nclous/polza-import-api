<?php

namespace App\Http\Controllers\Import;

use App\Models\Wp\PostMeta;
use App\Models\Wp\Product;
use App\Models\Wp\Term;
use App\Models\Wp\TermMeta;
use App\Models\Wp\TermRelationships;
use App\Models\Wp\TermTaxonomy;
use App\Services\Import1CService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function syncGroups(Import1CService $import1CService): array
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
            if (isset($group['id'])) continue;

            $term = Term::create([ 'name' => $group['name'], 'slug' => Str::slug($group['name']) ]);
            $termMetaGuid = TermMeta::make([ 'meta_key' => TermMeta::META_KEY_GUID, 'meta_value' => $guid ]);
            $termTaxonomyCategory = TermTaxonomy::make([ 'taxonomy' => 'product_cat', 'parent' => isset($group['parent']) ? $groups[$group['parent']]['id'] : 0 ]);

            $term->meta()->save($termMetaGuid);
            $term->taxonomies()->save($termTaxonomyCategory);

            $group['id'] = $term->term_id;
        }

        $import1CService->setSyncGroupsCache($groups);

        return [ 'count' => count($groups) ];
    }

    public function syncProducts(Import1CService $import1CService)
    {
        // Берём товары из XML
        $products = $import1CService->getProductsFromXml();
        $currentTime = Carbon::now();

        $defaultAttributes = (new Product)->getAttributes();

        foreach ($products as $guid => &$productsItem) {
            if (isset($productsItem['id'])) continue;

            $product = Product::create([
                'post_title' => $productsItem['name'],
                'post_name' => Str::slug($productsItem['name']),
                'guid' => $guid,
                'post_content' => $productsItem['content'] ?? '',
                'post_date' => $currentTime,
                'post_date_gmt' => $currentTime,
                'post_modified' => $currentTime,
                'post_modified_gmt' => $currentTime,
            ] + $defaultAttributes);

            $meta = [];

            foreach ($productsItem['meta'] ?? [] as $key => $value) {
                $meta[] = PostMeta::make([ 'meta_key' => $key, 'meta_value' => $value ]);
            }

            $product->meta()->saveMany($meta);

            if ($productsItem['group'] ?? null) {
                $product->termRelationships()->save(TermRelationships::make([
                    'term_taxonomy_id' => $productsItem['group']
                ]));
            }
        }

        return [ 'count' => count($products) ];
    }
}
