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

class ModelsTest extends \PHPUnit_Framework_TestCase
{
	static private $connections;
	static private $definitions;
	static private $models;

	static public function setupBeforeClass()
	{
		self::$connections = new Connections([

			'primary' => 'sqlite::memory:'

		]);

		self::$definitions = [

			'articles' => [

				Model::CLASSNAME => __CLASS__ . '\ArticlesModel',
				Model::SCHEMA => [

					'fields' => [

						'article_id' => 'serial',
						'title' => 'varchar'

					]

				]

			],

			'comments' => [

				Model::CLASSNAME => __CLASS__ . '\CommentsModel',
				Model::SCHEMA => [

					'fields' => [

						'comment_id' => 'serial',
						'article_id' => 'foreign',
						'body' => 'text'

					]

				]

			],

			'other' => [

				Model::SCHEMA => [

					'fields' => [

						'id' => 'serial',
						'value' => 'integer'

					]

				]

			]

		];

		self::$models = new Models(self::$connections, self::$definitions);
	}

	public function test_get_connections()
	{
		$this->assertSame(self::$connections, self::$models->connections);
	}

	public function test_get_definitions()
	{
		$this->assertInternalType('array', self::$models->definitions);
		$this->assertEquals([ 'articles', 'comments', 'other' ], array_keys(self::$models->definitions));
	}

	public function test_class_name()
	{
		$this->assertInstanceOf(__CLASS__ . '\ArticlesModel', self::$models['articles']);
		$this->assertInstanceOf(__CLASS__ . '\CommentsModel', self::$models['comments']);
		$this->assertInstanceOf('ICanBoogie\ActiveRecord\Model', self::$models['other']);
	}
}

namespace ICanBoogie\ActiveRecord\ModelsTest;

use ICanBoogie\ActiveRecord\Model;

class ArticlesModel extends Model
{

}

class CommentsModel extends Model
{

}