<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Query;

/**
 * @extends Query<Article>
 */
class ArticleQuery extends Query
{
    /**
     * @see self::ordered()
     */
    public self $ordered
    {
        get => $this->ordered();
    }

    /**
     * @return $this
     */
    public function ordered(int $direction = -1): self
    {
        return $this->order('date ' . ($direction < 0 ? 'DESC' : 'ASC'));
    }
}
