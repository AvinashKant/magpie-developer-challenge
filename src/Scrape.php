<?php

namespace App;

use App\ParserInterface;
use App\Product;

require 'vendor/autoload.php';

class Scrape
{
    private array $products = [];

    private $parser;

    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    public function run(): void
    {
        $document = ScrapeHelper::fetchDocument('https://www.magpiehq.com/developer-challenge/smartphones');

        $productsDivs = $document->filter('body > div.container > div#products')->eq(0);

        /**
         * Parse the products from the first page
         */
        $this->products[] = $this->parser->parse($productsDivs);

        /**
         * Travel all pagination links and parse the products from each page
         */
        $pages = ScrapeHelper::getPages($productsDivs, $document->getBaseHref());

        if (is_array($pages) && count($pages) > 0) {
            foreach ($pages as $page) {
                $document = ScrapeHelper::fetchDocument($page);
                $this->products[] = $this->parser->parse($document->filter('body > div.container > div#products')->eq(0));
            }
        }
        file_put_contents('output.json', json_encode($this->products));
    }

}

$products = new Product;
$scrape = new Scrape($products);
$scrape->run();
