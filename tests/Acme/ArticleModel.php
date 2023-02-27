<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;

class ArticleModel extends Model
{
    protected function scope_ordered(Query $query, $direction = -1): Query
    {
        return $query->order('date ' . ($direction < 0 ? 'DESC' : 'ASC'));
    }
}
