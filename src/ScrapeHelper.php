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

    public static function extractDateFromText($text)
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
