<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Node>
 */
class NodeModel extends Model
{
    protected static string $activerecord_class = Node::class;
}
