<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

abstract class ProductParser
{
    public abstract function parse(Crawler $document): array;
}


