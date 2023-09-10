<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Query;

/**
 * @extends Query<Article>
 *
 * @property-read self $ordered
 * @uses self::ordered
 * @uses self::get_ordered
 */
class ArticleQuery extends Query
{
    protected function get_ordered(): self
    {
        return $this->ordered();
    }

    /**
     * @return $this
     */
    public function ordered(int $direction = -1): self
    {
        return $this->order('date ' . ($direction < 0 ? 'DESC' : 'ASC'));
    }
}
