<?php

namespace App\Http\Controllers\Import;

use App\Models\Wp\Term;
use App\Models\Wp\TermMeta;
use App\Models\Wp\TermTaxonomy;
use App\Services\Import1CService;
use Illuminate\Routing\Controller;
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
}
