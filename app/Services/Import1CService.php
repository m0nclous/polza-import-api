<?php

namespace App\Services;

use App\Models\Wp\Term;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SimpleXMLElement;

class Import1CService extends AbstractFile1CService
{
    protected string $fileName = 'import0_1.xml';

    public function __construct()
    {
        $this->xml = $this->getXml();
    }

    public function getGroupsFromXml(SimpleXMLElement $simpleXMLElements = null): array
    {
        $array = [];
        $simpleXMLElements = $simpleXMLElements ?? $this->xml->{'Классификатор'}->{'Группы'};

        foreach ($simpleXMLElements->{'Группа'} as $simpleXMLElement) {
            $guid = (string) $simpleXMLElement->{'Ид'};

            $array[$guid] = [
                'name' => (string) $simpleXMLElement->{'Наименование'},
            ];

            $childSimpleXMLElements = $simpleXMLElement->{'Группы'};

            if ($childSimpleXMLElements) {
                $array = $array + array_map(fn ($item) => $item + ['parent' => $guid], $this->getGroupsFromXml($childSimpleXMLElements));
            }
        }

        return $array;
    }

    public function createGroups(array $groups): bool
    {
        $termsData = array_map(fn ($item) => [
            'name' => $item['name'],
            'slug' => $item['slug'] ?? Str::slug($item['name']),
            'meta' => array_map(fn ($meta) => [
                'meta_key' => $meta['key'],
                'meta_value' => $meta['value'],
            ], $item['meta'] ?? [])
        ], $groups);

        foreach ($termsData as $data) {
            $term = Term::create($data);
        }

        return Term::insert($termsData);
    }

    public function setSyncGroupsCache(array $groups): void
    {
        Cache::set('syncGroups', $groups, Carbon::now()->addHour());
    }

    public function getSyncGroupsCache(): ?array
    {
        return Cache::get('syncGroups');
    }
}