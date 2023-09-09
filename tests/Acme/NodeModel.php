<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord\Model;

/**
 * @extends Model<int, Node>
 */
#[Model\Record(Node::class)]
class NodeModel extends Model
{
}
