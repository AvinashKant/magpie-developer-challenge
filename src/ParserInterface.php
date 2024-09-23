<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

interface ParserInterface
{
    public function parse(Crawler $document);
}
