<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

class Product implements ParserInterface
{
    public function __construct()
    {
    }

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
            "shippingDate" => $this->extractDateFromText($shippingText),
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

    private function extractDateFromText($text)
    {
        /**
         * Regular expression to match various date formats
         */
        $regex = '/\d{4}-\d{2}-\d{2}|\d{2} (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{4}/';

        /**
         * Find all matches in the text
         */
        preg_match_all($regex, $text, $matches);

        /**
         * If matches found, return the first match (assuming the first date is the most relevant)
         */
        if (!empty($matches[0])) {
            return $matches[0][0];
        }
        /**
         * If no matches found, return null or an appropriate error message
         */
        return null;
    }
}
