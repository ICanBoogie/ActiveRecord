<?php

namespace ICanBoogie\ActiveRecord\ModelTest;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;

class ArticleModel extends Model
{
	protected function scope_ordered(Query $query, $direction = -1)
	{
		return $query->order('date ' . ($direction < 0 ? 'DESC' : 'ASC'));
	}
}
