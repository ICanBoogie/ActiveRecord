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

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_test_implementing
	 */
	public function test_implementing($classname, $ctor_args)
	{
		$r = new \ReflectionClass(__NAMESPACE__ . '\\' . $classname);
		$exception = $r->newInstanceArgs($ctor_args);
		$this->assertInstanceOf('ICanBoogie\ActiveRecord\Exception', $exception);
	}

	public function provide_test_implementing()
	{
		$fake_model = new Model([

			Model::CONNECTION => new Connection('sqlite::memory:'),
			Model::NAME => 'testing',
			Model::SCHEMA => [

				'fields' => [

					'id' => 'serial'

				]

			]

		]);

		return [

			[ 'ConnectionNotDefined', [ 'connection-name' ] ],
			[ 'ConnectionNotEstablished', [ 'connection-name' ] ],
			[ 'ConnectionAlreadyEstablished', [ 'connection-name' ] ],

			[ 'RelationError', [ "message" ] ],

			[ 'RecordNotFound', [ "message", [] ] ],
			[ 'ScopeNotDefined', [ 'scope-name', $fake_model ] ],

			[ 'ModelNotDefined' , [ 'model-name' ] ],
			[ 'ModelAlreadyInstantiated' , [ 'model-name' ] ],

			[ 'StatementInvalid' , [ 'statement' ] ],
			[ 'UnableToSetFetchMode' , [ 'mode' ] ]

		];
	}
}