<?php

namespace App\Services;

use App\Models\Wp\TermMeta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class Import1CService extends AbstractFile1CService
{
    protected string $fileName = 'import0_1.xml';

    protected const PRODUCT_PROPERTY_DICTIONARY = [
        // Описание
        'e7892447-f15d-11ec-99bc-002590c63e13' => [
            'name' => 'content',
        ],

        // Описание
        '2f582174-ae22-11ec-babb-d8bbc10dcaeb' => [
            'name' => 'application-instruction',
        ],

        // Кол-во капсул
        '5aa45d61-9427-11e9-a757-f44d30ea45fe' => [
            'name' => 'number-of-capsules',
        ],

        // Кол-во порций
        '61ab30e3-9427-11e9-a757-f44d30ea45fe' => [
            'name' => 'servings',
        ],

        // Кол-во таблеток
        '61ab30e4-9427-11e9-a757-f44d30ea45fe' => [
            'name' => 'number-of-tablets',
        ],

        // Выгружать DFS
        'e94649e5-e9a4-11ec-99bc-002590c63e13' => [
            'name' => 'upload-dfs',
            'yesOrNotToNumber' => true
        ],

        // Подлежит сертификации
        '2f582173-ae22-11ec-babb-d8bbc10dcaeb' => [
            'name' => 'subject-to-certification',
            'yesOrNotToNumber' => true
        ],

        // Сертификат
        '2f582175-ae22-11ec-babb-d8bbc10dcaeb' => [
            'name' => 'certificate',
        ],

        // Состав
        '2f582176-ae22-11ec-babb-d8bbc10dcaeb' => [
            'name' => 'compound',
        ],

        // Таблица состава
        '2f582177-ae22-11ec-babb-d8bbc10dcaeb' => [
            'name' => 'composition-table',
        ],

        // Процент ДФС
        '087ea9f8-e4e8-11eb-a7aa-f44d30ea45fe' => [
            'name' => 'percent-dfs',
        ],

        // Основной ингредиент
        '61ab30e5-9427-11e9-a757-f44d30ea45fe' => [
            'name' => 'main-ingredient',
        ],

        // Размер
        '67b1f0a6-9427-11e9-a757-f44d30ea45fe' => [
            'name' => 'size',
        ],

        // Страна
        '67b1f0a7-9427-11e9-a757-f44d30ea45fe' => [
            'name' => 'country',
        ],

        // Упаковка
        '67b1f0a8-9427-11e9-a757-f44d30ea45fe' => [
            'name' => 'package',
        ],

        // Форма выпуска
        'c1f02728-9429-11e9-a757-f44d30ea45fe' => [
            'name' => 'release-form',
        ],

        // Цвет
        'c1f02729-9429-11e9-a757-f44d30ea45fe' => [
            'name' => 'color',
        ],

        // Цель
        'c957dc4c-9429-11e9-a757-f44d30ea45fe' => [
            'name' => 'target',
        ],

        // Группа сайта DFS
        '268d6b41-57e6-11ec-bab6-d8bbc10dcaeb' => [
            'name' => 'dfs-site-group',
        ],

        // Бренд
        '160c4979-9427-11e9-a757-f44d30ea45fe' => [
            'name' => 'brand',
        ],

        // Группа сайта Польза
        '542ba198-f143-11ec-99bc-002590c63e13' => [
            'name' => 'site-group',
        ],
    ];

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

    public function getPropertiesFromXml(): array
    {
        $array = [];

        foreach ($this->xml->{'Классификатор'}->{'Свойства'}->{'Свойство'} as $simpleXMLElement) {
            $guid = (string) $simpleXMLElement->{'Ид'};
            $type = (string) $simpleXMLElement->{'ТипЗначений'};

            if ($type === 'Справочник') {
                foreach ($simpleXMLElement->{'ВариантыЗначений'}->{$type} as $simpleXMLElementDictionary) {
                    $simpleXMLElementDictionaryGuid = (string) $simpleXMLElementDictionary->{'ИдЗначения'};
                    $simpleXMLElementDictionaryValue = (string) $simpleXMLElementDictionary->{'Значение'};

                    $array[$guid][$simpleXMLElementDictionaryGuid] = $simpleXMLElementDictionaryValue;
                }
            } else {
                $array[$guid] = (string) $simpleXMLElement->{'Наименование'};
            }
        }

        return $array;
    }

    public function getProductsFromXml($skipDeleted = true): array
    {
        $array = [];
        $propertiesFromXml = $this->getPropertiesFromXml();
        $groupsFromApp = $this->getGroupsFromApp();

        foreach ($this->xml->{'Каталог'}->{'Товары'}->{'Товар'} as $simpleXMLElement) {
            $simpleXMLElementAttributes = ((array) $simpleXMLElement->attributes())['@attributes'] ?? [];

            if ($skipDeleted && in_array('Удалён', $simpleXMLElementAttributes)) continue;

            $guid = (string) $simpleXMLElement->{'Ид'};
            $sku = (string) $simpleXMLElement->{'Артикул'};
            $content = (string) $simpleXMLElement->{'Описание'};

            $meta = [
                '_manage_stock' => 'yes',
                '_stock' => 0,
                '_stock_status' => 'outofstock',
            ];

            $array[$guid] = [
                'name' => (string) $simpleXMLElement->{'Наименование'},
            ];

            if ($sku) $meta['_sku'] = $sku;
            if ($content) $array[$guid]['content'] = $content;

            foreach ($simpleXMLElement->{'ЗначенияСвойств'}->{'ЗначенияСвойства'} ?? [] as $simpleXMLElementProperty) {
                $propertyGuid = (string) $simpleXMLElementProperty->{'Ид'};

                $propertyValue = (string) $simpleXMLElementProperty->{'Значение'};

                if (is_array($propertiesFromXml[$propertyGuid])) {
                    $propertyValue = $propertiesFromXml[$propertyGuid][$propertyValue] ?? $propertyValue;
                }

                $propertyInfo = self::PRODUCT_PROPERTY_DICTIONARY[$propertyGuid] ?? null;

                if ($propertyInfo === null) {
                    Log::info('Несуществующий $propertyGuid', [ '$propertyGuid' => $propertyGuid ]);
                    continue;
                }

                if ($propertyInfo['name'] === 'content' && !empty($propertyValue)) {
                    $array[$guid]['content'] = $propertyValue;
                    continue;
                }

                if ($propertyInfo['name'] === 'site-group' && !empty($propertyValue)) {
                    $array[$guid]['group'] = $groupsFromApp[(string) $simpleXMLElementProperty->{'Значение'}] ?? null;
                    continue;
                }

                if ($propertyInfo['yesOrNotToNumber'] ?? false) {
                    $propertyValue = $propertyValue === 'Да' ? '1' : '0';
                }

                $meta[$propertyInfo['name']] = $propertyValue;
            }

            if ($meta) $array[$guid]['meta'] = $meta;
        }

        return $array;
    }

    public function getGroupsFromApp(): array
    {
        return TermMeta::where('meta_key', TermMeta::META_KEY_GUID)
            ->join('term_taxonomy', 'termmeta.term_id', '=', 'term_taxonomy.term_id')
            ->get(['term_taxonomy_id', 'meta_value'])
            ->keyBy('meta_value')
            ->map->term_taxonomy_id
            ->toArray();
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