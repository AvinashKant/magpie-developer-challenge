<?php

namespace App;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeHelper
{
    public static function fetchDocument(string $url): Crawler
    {
        $client = new Client();

        $response = $client->get($url);

        return new Crawler($response->getBody()->getContents(), $url);
    }

    public static function getPages(Crawler $documentNode, string $baseUri): array
    {
        $pages = $documentNode->filter('#pages a')->each(function (Crawler $node, $i) use ($baseUri) {
            $param = explode('?', $node->attr('href'));
            return (!$node->matches('.active')) ? $baseUri . '?' . $param[1] : "";
        });
        return array_diff($pages, [null]);
    }

}
