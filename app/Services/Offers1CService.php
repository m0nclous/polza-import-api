<?php

namespace App\Services;

use Illuminate\Support\Str;

class Offers1CService extends AbstractFile1CService
{
    protected string $fileName = 'offers0_1.xml';

    public function __construct()
    {
        $this->xml = $this->getXml();
    }

    public function getOffersFromXml(array $array = []): array
    {
        foreach ($this->xml->{'ПакетПредложений'}->{'Предложения'}->{'Предложение'} as $simpleXMLElement)
        {
            $guidParts = explode('#', (string) $simpleXMLElement->{'Ид'});
            $guid = $guidParts[0];
            $guidVariation = $guidParts[1] ?? null;

            if ($guidVariation) {
                $meta = [];
            } else {
                $meta = $array[$guid]['meta'];
            }

            $meta['_stock'] = (int) $simpleXMLElement->{'Количество'};
            $meta['_regular_price'] = (int) $simpleXMLElement->{'Цены'}->{'Цена'}->{'ЦенаЗаЕдиницу'};

            if ($guidVariation) {
                $array[$guid]['meta']['_stock'] += $meta['_stock'];
                $array[$guid]['meta']['_stock_status'] = $array[$guid]['meta']['_stock'] > 0 ? 'instock' : 'outofstock';
            } else {
                $meta['_manage_stock'] = 'yes';
                $meta['_stock_status'] = $meta['_stock'] > 0 ? 'instock' : 'outofstock';
            }

            $meta['_price'] = $meta['_regular_price'];

            foreach ($simpleXMLElement->{'Склад'} as $simpleXMLElementStock) {
                $attributes = $simpleXMLElementStock->attributes();
                $stockGuid = (string) $attributes->{'ИдСклада'};
                $stockValue = (int) $attributes->{'КоличествоНаСкладе'};
                $meta["_stock_$stockGuid"] = $stockValue;
            }

            $array[$guidVariation ?? $guid]['name'] = (string) $simpleXMLElement->{'Наименование'};
            $array[$guidVariation ?? $guid]['meta'] = $meta;

            if ($guidVariation) {
                $variantKey = 'variant';

                $variationName = trim(
                    preg_replace(
                        '/(' . preg_quote($array[$guid]['name']) . ')|\(|\)/',
                        '',
                        $array[$guidVariation]['name']
                    )
                );

                $array[$guidVariation]['parent'] = $guid;
                $array[$guidVariation]['meta']['attribute_' . $variantKey] = $variationName;

                $_product_attributes = $array[$guid]['meta']['_product_attributes'] ?? [
                    $variantKey => [
                        'name' => 'Вариант',
                        'value' => '',
                        'position' => 0,
                        'is_visible' => 0,
                        'is_variation' => 1,
                        'is_taxonomy' => 0
                    ]
                ];

                if (empty($_product_attributes[$variantKey]['value'])) {
                    $_product_attributes[$variantKey]['value'] = $variationName;
                } else {
                    $_product_attributes[$variantKey]['value'] .= ' | ' . $variationName;
                }

                if (!isset($array[$guid]['meta']['_default_attributes']) && $meta['_stock'] > 0) {
                    $array[$guid]['meta']['_default_attributes'] = [$variantKey => $variationName];
                }

                $array[$guid]['meta']['_product_attributes'] = $_product_attributes;
            }
        }

        foreach ($array as &$item) {
            if (isset($item['meta']['_product_attributes'])) {
                $item['meta']['_product_attributes'] = serialize($item['meta']['_product_attributes']);
            }

            if (isset($item['meta']['_default_attributes'])) {
                $item['meta']['_default_attributes'] = serialize($item['meta']['_default_attributes']);
            }
        }

        return $array;
    }
}
