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

class RelationTest extends \PHPUnit_Framework_TestCase
{
	private $model;

	public function setUp()
	{
		$activerecord = $this
			->getMockBuilder(ActiveRecord::class)
			->disableOriginalConstructor()
			->getMock();

		$model = $this
			->getMockBuilder(Model::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_activerecord_class', 'get_models'])
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
			->setMethods([ 'offsetGet' ])
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

	/**
	 * @expectedException \ICanBoogie\ActiveRecord\ActiveRecordClassNotValid
	 */
	public function test_should_throw_exception_when_default_activerecord_class()
	{
		$model = $this
			->getMockBuilder(Model::class)
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$related = 'comments';

		$this
			->getMockBuilder(Relation::class)
			->setConstructorArgs([ $model, $related ])
			->getMockForAbstractClass();
	}

	public function test_get_parent()
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
