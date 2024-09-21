<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

class Product
{

    private Crawler $rawData;

    private array $products = [];

    public function __construct(Crawler $document)
    {
        $this->rawData = $document;
    }

    public function process(): array
    {
        $productsDivs = $this->rawData->filter('body > div.container > div#products')->eq(0);
        $this->parse($productsDivs);

        /**
         * Travel all pagination links and parse the products from each page
         */
        $pages = $this->getPages($productsDivs, $this->rawData->getBaseHref());

        if (is_array($pages) && count($pages) > 0) {
            foreach ($pages as $page) {
                $document = ScrapeHelper::fetchDocument($page);
                $this->parse($document->filter('body > div.container > div#products')->eq(0));
            }
        }

        return $this->products;

    }

    public function getPages(Crawler $documentNode, string $baseUri): array
    {
        $pages = $documentNode->filter('#pages a')->each(function (Crawler $node, $i) use ($baseUri) {
            $param = explode('?', $node->attr('href'));
            return (!$node->matches('.active')) ? $baseUri . '?' . $param[1] : "";
        });
        return array_diff($pages, [null]);
    }

    private function parse(Crawler $document)
    {
        $document->filter('div.product')->each(function (Crawler $node, $i) {
            $childDivs = $node->filter('div')->children('div > div');

            $childDivs->eq(0)->filter('div > span')->each(function (Crawler $colorDiv, $i) use ($node, $childDivs) {

                $title = $node->filter('h3 > span.product-name')->eq(0)->text();
                $capacity = $node->filter('h3 > span.product-capacity')->eq(0)->text();
                $imageUrl = $node->filter('img')->image()->getUri();

                $color = $colorDiv->attr('data-colour');

                $price = $childDivs->eq(1)->text();
                $availability = $childDivs->eq(2)->text();
                $shippingText = $childDivs->eq(3)->text();

                $this->products[] = $this->createStructure($title, $capacity, $imageUrl, $price, $availability, $shippingText, $color);

            });
        });
    }

    private function createStructure(string $title, $capacity, string $imageUrl, $price, string $availability, string $shippingText, string $color): array
    {
        $availability = explode(":", $availability);
        return [
            "title" => $title,
            "price" => $price,
            "imageUrl" => $imageUrl,
            "capacityMB" => $capacity,
            "colour" => $color,
            "availabilityText" => $availability[1] ?? null,
            "isAvailable" => $this->isStockAvailable($availability[1] ?? null),
            "shippingText" => $shippingText,
            "shippingDate" => $this->extractDateFromText($shippingText),
        ];
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
