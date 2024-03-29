<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord\Validate\Reader;

use ICanBoogie\ActiveRecord\Validate\Reader\RecordAdapter;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\NodeModel;
use Test\ICanBoogie\Fixtures;

/**
 * @group validate
 * @small
 */
final class RecordAdapterTest extends TestCase
{
    public function test_adapter(): void
    {
        $models = Fixtures::only_models('nodes');

        $v = uniqid();

        $record = new Node($models->model_for_record(Node::class));
        $record->title = $v;

        $reader = new RecordAdapter($record);

        $this->assertSame($record, $reader->record);
        $this->assertSame($v, $record->title);
        $this->assertNull($reader->read('title' . uniqid()));
    }
}
