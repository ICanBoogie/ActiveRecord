<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\Acme\Node;

class Article extends Node
{
    public string $body;
    public string $date;
    public ?int $rating;
}
