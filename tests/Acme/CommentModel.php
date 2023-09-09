<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Comment>
 */
class CommentModel extends Model
{
    protected static string $activerecord_class = Comment::class;
}
