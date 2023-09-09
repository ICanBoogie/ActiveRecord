<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Comment>
 */
class CommentModel extends Model
{
    public const activerecord_class = Comment::class;
}
