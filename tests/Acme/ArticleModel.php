<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;

#[Model\Record(Article::class)]
class ArticleModel extends NodeModel
{
    /**
     * @used-by Model::scope
     */
    protected function scope_ordered(Query $query, int $direction = -1): Query
    {
        return $query->order('date ' . ($direction < 0 ? 'DESC' : 'ASC'));
    }
}
