<?php

namespace App\Services;

class ImportService extends AbstractFile1CService
{
    static function parseProducts(): array
    {
        $import1CService = new Import1CService;
        $offers1CService = new Offers1CService;

        $products = $offers1CService->getOffersFromXml($import1CService->getProductsFromXml());

        foreach ($products as &$productsItem) {
            if (isset($productsItem['parent'])) {
                $productsItem['type'] = 'variation';
                $products[$productsItem['parent']]['type'] = 'variable';
            } else {
                $productsItem['type'] = 'simple';
            }

            if (is_array($productsItem['name'])) $productsItem['name'] = current($productsItem['name']);
        }

        return $products;
    }
}
