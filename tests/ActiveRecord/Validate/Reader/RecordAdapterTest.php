<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate\Reader;

use ICanBoogie\Acme\Node;
use ICanBoogie\ActiveRecord\Model;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Fixtures;

/**
 * @group validate
 * @small
 */
final class RecordAdapterTest extends TestCase
{
    public function test_adapter(): void
    {
        [ , $models ] = Fixtures::only_models([ 'nodes' ]);

        $p = 'property' . uniqid();
        $v = uniqid();

        $record = new Node($models->model_for_id('nodes'));
        $record->$p = $v;

        $reader = new RecordAdapter($record);

        $this->assertSame($record, $reader->record);
        $this->assertSame($v, $record->$p);
        $this->assertNull($reader->read('p' . uniqid()));
    }
}
