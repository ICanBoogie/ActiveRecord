<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord\Validate\Validator;

use ICanBoogie\ActiveRecord\Validate\Reader\RecordAdapter;
use ICanBoogie\ActiveRecord\Validate\Validator\Unique;
use ICanBoogie\Validate\Context;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\NodeModel;
use Test\ICanBoogie\Fixtures;

/**
 * @group validate
 * @medium
 */
final class UniqueTest extends TestCase
{
    public function test_normalize_options(): void
    {
        $column = uniqid();
        $validator = new Unique();
        $options = $validator->normalize_params([ Unique::OPTION_COLUMN => $column ]);
        $this->assertArrayHasKey(Unique::OPTION_COLUMN, $options);
        $this->assertArrayNotHasKey(0, $options);
        $this->assertSame($column, $options[Unique::OPTION_COLUMN]);
    }

    public function test_unique(): void
    {
        [ , $models ] = Fixtures::only_models([ 'nodes' ]);

        $models->install();
        $model = $models->model_for_class(NodeModel::class);

        $record = new Node($model);
        $record->title = $title = 'A title';
        $record->save();

        $context = new Context();
        $context->reader = new RecordAdapter($record);
        $context->validator_params = [ Unique::OPTION_COLUMN => 'title' ];

        $validator = new Unique();
        $actual = $validator->validate($title, $context);
        $this->assertTrue($actual);

        $record = new Node($model);
        $record->title = $title;

        $context->reader = new RecordAdapter($record);

        $actual = $validator->validate($title, $context);
        $this->assertFalse($actual);
    }
}
