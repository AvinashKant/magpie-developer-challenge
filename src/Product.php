<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

class Product extends ProductParser
{

    /**
     * Parse the products from the document
     */
    public function parse(Crawler $document): array
    {
        $products = [];
        $document->filter('div.product')->each(function (Crawler $node, $i) use (&$products) {
            $childDivs = $node->filter('div')->children('div > div');

            $childDivs->eq(0)->filter('div > span')->each(function (Crawler $colorDiv, $i) use ($node, $childDivs, &$products) {

                $title = $node->filter('h3 > span.product-name')->eq(0)->text();
                $capacity = $node->filter('h3 > span.product-capacity')->eq(0)->text();
                $imageUrl = $node->filter('img')->image()->getUri();

                $color = $colorDiv->attr('data-colour');

                $price = $childDivs->eq(1)->text();
                $availability = $childDivs->eq(2)->text();
                $shippingText = $childDivs->eq(3)->text();

                $products[] = $this->createStructure($title, $capacity, $imageUrl, $price, $availability, $shippingText, $color);

            });

        });
        return $products;
    }

    /**
     * Create a structured array from the product data
     */
    private function createStructure(string $title, string $capacity, string $imageUrl, $price, string $availability, string $shippingText, string $color): array
    {
        $availability = explode(":", $availability);
        return [
            "title" => $title,
            "price" => $price,
            "imageUrl" => $imageUrl,
            "capacityMB" => $this->changeCapacityToMB($capacity),
            "colour" => $color,
            "availabilityText" => $availability[1] ?? null,
            "isAvailable" => $this->isStockAvailable($availability[1] ?? null),
            "shippingText" => $shippingText,
            "shippingDate" => ScrapeHelper::extractDateFromText($shippingText),
        ];
    }

    private function changeCapacityToMB(string $capacity): string
    {
        if ((strpos(strtolower($capacity), 'gb') === false)) {
            return intval($capacity);
        }

        /**
         * Return value in MB
         */
        return intval($capacity) * 1000;
    }

    private function isStockAvailable(string $availability): bool
    {
        return (strpos(strtolower($availability), 'out of stock') === false);
    }

    
}
