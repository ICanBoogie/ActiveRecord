<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Comment>
 */
#[Model\Record(Comment::class)]
class CommentModel extends Model
{
}
