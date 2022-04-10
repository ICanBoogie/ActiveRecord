<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RelationTest extends TestCase
{
    private MockObject|Model $model;

    protected function setUp(): void
    {
        $activerecord = $this
            ->getMockBuilder(ActiveRecord::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model = $this
            ->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'get_activerecord_class', 'get_models' ])
            ->getMock();
        $model
            ->expects($this->any())
            ->method('get_activerecord_class')
            ->willReturn(get_class($activerecord));

        $comments = $this
            ->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->getMock();

        $models = $this
            ->getMockBuilder(ModelCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'offsetGet' ])
            ->getMock();

        $models
            ->expects($this->any())
            ->method('offsetGet')
            ->willReturn($comments);

        $model
            ->expects($this->any())
            ->method('get_models')
            ->willReturn($models);

        $this->model = $model;
    }

    public function test_should_throw_exception_when_default_activerecord_class(): void
    {
        $models = $this
            ->getMockBuilder(ModelCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $articles = new Model($models, [
            Model::CONNECTION => $connection,
            Model::NAME => 'testing',
            Model::SCHEMA => new Schema([
                'id'=> SchemaColumn::serial()
            ])
        ]);

        $this->expectException(ActiveRecordClassNotValid::class);

        new class($articles, 'comments', []) extends Relation
        {
            public function __invoke(ActiveRecord $record): mixed
            {
                return null;
            }
        };
    }

    public function test_get_parent(): void
    {
        $related = 'comments';

        $relation = $this
            ->getMockBuilder(Relation::class)
            ->setConstructorArgs([ $this->model, $related ])
            ->getMockForAbstractClass();

        /* @var $relation Relation */

        $this->assertSame($this->model, $relation->parent);
        $related = $relation->related;
        $this->assertInstanceOf(Model::class, $related);
        $this->assertSame($related, $relation->related);
    }
}
