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

/*
use ICanBoogie\ActiveRecord\ModelTest\A;
use ICanBoogie\DateTime;
*/

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\ModelTest\Article;
use ICanBoogie\ActiveRecord\ModelTest\ArticleModel;
use ICanBoogie\ActiveRecord\ModelTest\Brand;
use ICanBoogie\ActiveRecord\ModelTest\Car;
use ICanBoogie\ActiveRecord\ModelTest\Comment;
use ICanBoogie\ActiveRecord\ModelTest\Driver;
use ICanBoogie\DateTime;
use ICanBoogie\OffsetNotWritable;

class ModelTest extends \PHPUnit_Framework_TestCase
{
	private $prefix = 'myprefix';

	/**
	 * @var ConnectionCollection
	 */
	private $connections;

	/**
	 * @var ModelCollection
	 */
	private $models;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var int
	 */
	private $model_records_count;

	/**
	 * @var Model
	 */
	private $counts_model;

	/**
	 * @var int
	 */
	private $counts_records_count;

	public function setUp()
	{
		$connections = new ConnectionCollection([

			'primary' => [ 'dsn' => 'sqlite::memory:', 'options' => [

				ConnectionOptions::TABLE_NAME_PREFIX => $this->prefix

			] ]

		]);

		$models = new ModelCollection($connections, [

			'nodes' => [

				Model::SCHEMA => [

					'nid' => 'serial',
					'title' => 'varchar'

				]
			],

			'contents' => [

				Model::EXTENDING => 'nodes',
				Model::SCHEMA => [

					'body' => 'text',
					'date' => 'datetime'

				]
			],

			'articles' => [

				Model::ACTIVERECORD_CLASS => Article::class,
				Model::CLASSNAME => ArticleModel::class,
				Model::HAS_MANY => 'comments',
				Model::EXTENDING => 'contents'

			],

			'comments' => [

				Model::ACTIVERECORD_CLASS => Comment::class,
				Model::BELONGS_TO => 'articles',
				Model::SCHEMA => [

					'comment_id' => 'serial',
					'nid' => 'foreign',
					'body' => 'text'

				]
			],

			'counts' => [

				Model::SCHEMA => [

					'id' => 'serial',
					'name' => 'varchar',
					'date' => 'datetime'

				]
			]

		]);

		$models->install();

		$nodes = $models['articles'];
		$nodes->save([ 'title' => 'Madonna', 'body' => uniqid(), 'date' => '1958-08-16' ]);
		$nodes->save([ 'title' => 'Lady Gaga', 'body' => uniqid(), 'date' => '1986-03-28' ]);
		$nodes->save([ 'title' => 'Cat Power', 'body' => uniqid(), 'date' => '1972-01-21' ]);

		$counts = $models['counts'];
		$names = explode('|', 'one|two|three|four');

		foreach ($names as $name)
		{
			$counts->save([ 'name' => $name, 'date' => DateTime::now() ]);
		}

		$this->connections = $connections;
		$this->models = $models;
		$this->model = $nodes;
		$this->model_records_count = 3;
		$this->counts_model = $counts;
		$this->counts_records_count = count($names);
	}

	/**
	 * @expectedException \ICanBoogie\Prototype\MethodNotDefined
	 */
	public function test_call_undefined_method()
	{
		$this->models['nodes']->undefined_method();
	}

	public function test_should_instantiate_model()
	{
		/* @var $model Model */
		$models = $this->models;
		$model = $models['nodes'];

		$this->assertSame($models, $model->models);
		$this->assertSame($this->connections['primary'], $model->connection);
		$this->assertSame('nodes', $model->id);
		$this->assertSame($this->prefix . '_' . 'nodes', $model->name);
		$this->assertSame('nodes', $model->unprefixed_name);
	}

	public function test_get_parent()
	{
		$models = $this->models;
		$this->assertSame($models['nodes'], $models['contents']->parent);
		$this->assertSame($models['nodes'], $models['articles']->parent);
	}

	public function test_get_parent_model()
	{
		$models = $this->models;
		$this->assertSame($models['nodes'], $models['contents']->parent_model);
		$this->assertSame($models['contents'], $models['articles']->parent_model);
	}

	/**
	 * @requires PHP 5.6.0
	 */
	public function test_should_default_id_from_name()
	{
		$models = $this
			->getMockBuilder(ModelCollection::class)
			->disableOriginalConstructor()
			->getMock();

		$connection = $this
			->getMockBuilder(Connection::class)
			->disableOriginalConstructor()
			->getMock();

		$model = new Model($models, [

			Model::CONNECTION => $connection,
			Model::NAME => 'nodes',
			Model::SCHEMA => [

				'id' => 'serial'

			]
		]);

		$this->assertEquals('nodes', $model->id);
	}

	public function test_should_throw_not_found_when_record_does_not_exists()
	{
		$id = rand();

		try
		{
			$this->models['nodes']->find($id);
			$this->fail("Expected RecordNotFound");
		}
		catch (RecordNotFound $e)
		{
			$this->assertSame([ $id => null ], $e->records);
		}
	}

	public function test_should_throw_not_found_when_one_record_does_not_exists()
	{
		$id = rand();
		$model = $this->models['nodes'];
		$model->save([ 'title' => uniqid() ]);
		$model->save([ 'title' => uniqid() ]);

		try
		{
			$model->find(1, 2, $id);
			$this->fail("Expected RecordNotFound");
		}
		catch (RecordNotFound $e)
		{
			$records = $e->records;
			$message = $e->getMessage();

			$this->assertContains((string) $id, $message);

			$this->assertInstanceOf(ActiveRecord::class, $records[1]);
			$this->assertInstanceOf(ActiveRecord::class, $records[2]);
			$this->assertNull($records[$id]);
		}
	}

	public function test_should_throw_not_found_when_all_record_do_not_exists()
	{
		$id1 = rand();
		$id2 = rand();
		$id3 = rand();

		$model = $this->models['nodes'];

		try
		{
			$model->find($id1, $id2, $id3);
			$this->fail("Expected RecordNotFound");
		}
		catch (RecordNotFound $e)
		{
			$records = $e->records;
			$message = $e->getMessage();

			$this->assertContains((string) $id1, $message);
			$this->assertContains((string) $id2, $message);
			$this->assertContains((string) $id2, $message);

			$this->assertNull($records[$id1]);
			$this->assertNull($records[$id2]);
			$this->assertNull($records[$id3]);
		}
	}

	public function test_find_one()
	{
		$model = $this->models['articles'];
		$id = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
		$this->assertNotEmpty($id);

		$record = $model->find($id);
		$this->assertInstanceOf(Article::class, $record);
		$this->assertSame($record, $model->find($id));
	}

	public function test_find_many()
	{
		$model = $this->models['articles'];
		$id1 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
		$id2 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
		$id3 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
		$this->assertNotEmpty($id1);
		$this->assertNotEmpty($id2);
		$this->assertNotEmpty($id3);

		$records = $model->find($id1, $id2, $id3);

		$this->assertInternalType('array', $records);
		$this->assertInstanceOf(Article::class, $records[$id1]);
		$this->assertInstanceOf(Article::class, $records[$id2]);
		$this->assertInstanceOf(Article::class, $records[$id3]);

		$records2 = $model->find($id1, $id2, $id3);
		$this->assertSame($records[$id1], $records2[$id1]);
		$this->assertSame($records[$id2], $records2[$id2]);
		$this->assertSame($records[$id3], $records2[$id3]);
	}

	public function test_find_many_with_an_array()
	{
		$model = $this->models['articles'];
		$id1 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
		$id2 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
		$id3 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
		$this->assertNotEmpty($id1);
		$this->assertNotEmpty($id2);
		$this->assertNotEmpty($id3);

		$records = $model->find([ $id1, $id2, $id3 ]);

		$this->assertInternalType('array', $records);
		$this->assertInstanceOf(Article::class, $records[$id1]);
		$this->assertInstanceOf(Article::class, $records[$id2]);
		$this->assertInstanceOf(Article::class, $records[$id3]);

		$records2 = $model->find([ $id1, $id2, $id3 ]);
		$this->assertSame($records[$id1], $records2[$id1]);
		$this->assertSame($records[$id2], $records2[$id2]);
		$this->assertSame($records[$id3], $records2[$id3]);
	}

	public function test_offsets()
	{
		$model = $this->models['nodes'];
		$this->assertFalse(isset($model[uniqid()]));
		$id = $model->save([ 'title' => uniqid() ]);
		$this->assertTrue(isset($model[$id]));
		unset($model[$id]);
		$this->assertFalse(isset($model[$id]));

		try
		{
			$model[$id] = null;
			$this->fail("Expected OffsetNotWritable");
		}
		catch (OffsetNotWritable $e)
		{

		}
	}

	public function test_new_record()
	{
		$model = $this->models['articles'];
		$title = 'Title ' . uniqid();
		$record = $model->new([ 'title' => $title ]);

		$this->assertInstanceOf(Article::class, $record);
		$this->assertSame($title, $record->title);
		$this->assertSame($model, $record->model);
	}

	/**
	 * @dataProvider provide_test_readonly_properties
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 * @param string $property Property name.
	 */
	public function test_readonly_properties($property)
	{
		$this->model->$property = null;
	}

	public function provide_test_readonly_properties()
	{
		$properties = 'id|activerecord_class|exists|count|all|one';
		return array_map(function($v) { return (array) $v; }, explode('|', $properties));
	}

	public function test_get_exists()
	{
		$this->assertTrue($this->model->exists);
	}

	public function test_get_count()
	{
		$this->assertEquals($this->model_records_count, $this->model->count);
	}

	public function test_get_all()
	{
		$all = $this->model->all;
		$this->assertInternalType('array', $all);
		$this->assertEquals($this->model_records_count, count($all));
		$this->assertContainsOnlyInstancesOf(ActiveRecord::class, $all);
	}

	public function test_get_one()
	{
		$one = $this->model->one;
		$this->assertInstanceOf(ActiveRecord::class, $one);
	}

	/**
	 * @dataProvider provide_test_initiate_query
	 *
	 * @param $method
	 * @param $args
	 */
	public function test_initiate_query($method, $args)
	{
		$this->assertInstanceOf(Query::class, call_user_func_array([ $this->model, $method], $args));
	}

	public function provide_test_initiate_query()
	{
		return [

			[ 'select', [ 'id, name' ] ],
			[ 'join', [ 'JOIN some_other_table' ] ],
			[ 'where', [ '1=1' ] ],
			[ 'group', [ 'name' ] ],
			[ 'order', [ 'name' ] ],
			[ 'limit', [ '12' ] ],
			[ 'offset', [ '12' ] ]

		];
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidConnection()
	{
		$models = $this
			->getMockBuilder(ModelCollection::class)
			->disableOriginalConstructor()
			->getMock();

		$model = new Model($models, [

			Model::NAME => 'tests',
			Model::CONNECTION => 'invalid_connection',
			Model::SCHEMA => [

				'id' => 'serial',
				'name' => 'varchar',
				'date' => 'timestamp'

			]
		]);
	}

	public function test_has_scope()
	{
		$model = $this->models['articles'];

		$this->assertTrue($model->has_scope('ordered'));
		$this->assertFalse($model->has_scope(uniqid()));
	}

	public function test_scope_as_property()
	{
		$a = $this->model;
		$q = $a->ordered;
		$this->assertInstanceOf(Query::class, $q);

		$record = $q->one;
		$this->assertInstanceOf(ActiveRecord::class, $record);
		$this->assertEquals('Lady Gaga', $record->title);
	}

	public function test_scope_as_method()
	{
		$a = $this->model;
		$q = $a->ordered(1);
		$this->assertInstanceOf(Query::class, $q);

		$record = $q->one;
		$this->assertInstanceOf(ActiveRecord::class, $record);
		$this->assertEquals('Madonna', $record->title);
	}

	/**
	 * @expectedException \ICanBoogie\ActiveRecord\ScopeNotDefined
	 */
	public function test_scope_not_defined()
	{
		$this->model->scope('undefined' . uniqid());
	}

	/**
	 * @expectedException \ICanBoogie\ActiveRecord\ScopeNotDefined
	 */
	public function test_scope_not_defined_from_query()
	{
		$this->model->ordered->undefined_scope();
	}

	/*
	 * Record existence
	 */

	/**
	 * `exists()` must return `true` when a record or all the records of a subset exist.
	 */
	public function test_exists_true()
	{
		$m = $this->counts_model;
		$this->assertTrue($m->exists(1));
		$this->assertTrue($m->exists(1, 2, 3));
		$this->assertTrue($m->exists([ 1, 2, 3 ]));
	}

	/**
	 * `exists()` must return `false` when a record or all the records of a subset don't exist.
	 */
	public function test_exists_false()
	{
		$m = $this->counts_model;
		$u = rand(999, 9999);

		$this->assertFalse($m->exists($u));
		$this->assertFalse($m->exists($u+1, $u+2, $u+3));
		$this->assertFalse($m->exists([ $u+1, $u+2, $u+3 ]));
	}

	/**
	 * `exists()` must return an array when some records of a subset don't exist.
	 */
	public function test_exists_mixed()
	{
		$m = $this->counts_model;
		$u = rand(999, 9999);
		$a = [ 1 => true, $u => false, 3 => true ];

		$this->assertEquals($a, $m->exists(1, $u, 3));
		$this->assertEquals($a, $m->exists([ 1, $u, 3 ]));
	}

	public function test_exists_condition()
	{
		$this->assertTrue($this->counts_model->filter_by_name('one')->exists);
		$this->assertFalse($this->counts_model->filter_by_name('one ' . uniqid())->exists);
	}

	public function test_belongs_to()
	{
		$models = new ModelCollection($this->connections, [

			'drivers' => [

				Model::ACTIVERECORD_CLASS => Driver::class,
				Model::SCHEMA => [

					'driver_id' => 'serial',
					'name' => 'varchar'

				]
			],

			'brands' => [

				Model::ACTIVERECORD_CLASS => Brand::class,
				Model::SCHEMA => [

					'brand_id' => 'serial',
					'name' => 'varchar'

				]
			],

			'cars' => [

				Model::ACTIVERECORD_CLASS => Car::class,
	// 			Model::BELONGS_TO => [ $drivers, $brands ],
				Model::SCHEMA => [

					'car_id' => 'serial',
					'driver_id' => 'foreign',
					'brand_id' => 'foreign',
					'name' => 'varchar'

				]
			]
		]);

		$models->install();

		$drivers = $models['drivers'];
		$brands = $models['brands'];
		$cars = $models['cars'];

		$cars->belongs_to($drivers, $brands);

		$car = $cars->new([ 'name' => '4two' ]);
		$this->assertInstanceOf(Car::class, $car);
		$this->assertNull($car->driver);
		$this->assertNull($car->brand);

		# driver

		$driver = $drivers->new([ 'name' => 'Madonna' ]);
		$this->assertInstanceOf(Driver::class, $driver);
		$driver_id = $driver->save();

		# brand

		$brand = $brands->new([ 'name' => 'Smart' ]);
		$this->assertInstanceOf(Brand::class, $brand);
		$brand_id = $brand->save();

		$car->driver_id = $driver_id;
		$car->brand_id = $brand_id;
		$car->save();

		$this->assertInstanceof(Driver::class, $car->driver);
		$this->assertInstanceof(Brand::class, $car->brand);

		$car->driver_id = null;
		$this->assertNull($car->driver_id);
		$car->driver = $driver;
		$this->assertEquals($driver->driver_id, $car->driver_id);
	}

	public function test_cache_should_be_revoked_on_save()
	{
		$name1 = uniqid();
		$name2 = uniqid();

		$model = $this->counts_model;
		$id = $model->save([ 'name' => $name1, 'date' => DateTime::now() ]);
		$record = $model[$id];
		$model->save([ 'name' => $name2 ], $id);
		$record_now = $model[$id];

		$this->assertEquals($name1, $record->name);
		$this->assertEquals($name2, $record_now->name);
		$this->assertNotSame($record, $record_now);
	}


	/**
	 * @dataProvider provide_test_querying
	 *
	 * @param callable $callback
	 * @param string $expected
	 */
	public function test_querying($callback, $expected)
	{
		$this->assertSame($expected, (string) $callback($this->model));
	}

	public function provide_test_querying()
	{
		$p = $this->prefix;
		$l = Query::LIMIT_MAX;

		return [

			[ function(Model $m) { return $m->select('nid, UPPER(name)'); }, <<<EOT
SELECT nid, UPPER(name) FROM `{$p}_contents` `content` INNER JOIN `{$p}_nodes` `node` USING(`nid`)
EOT
			],

			[ function (Model $m) { return $m->join('INNER JOIN other USING(nid)'); }, <<<EOT
SELECT * FROM `{$p}_contents` `content` INNER JOIN `{$p}_nodes` `node` USING(`nid`) INNER JOIN other USING(nid)
EOT
			],

			[ function (Model $m) { return $m->where([ 'nid' => 1, 'name' => 'madonna' ]); }, <<<EOT
SELECT * FROM `{$p}_contents` `content` INNER JOIN `{$p}_nodes` `node` USING(`nid`) WHERE (`nid` = ? AND `name` = ?)
EOT
			],

			[ function (Model $m) { return $m->group('name'); }, <<<EOT
SELECT * FROM `{$p}_contents` `content` INNER JOIN `{$p}_nodes` `node` USING(`nid`) GROUP BY name
EOT
			],

			[ function (Model $m) { return $m->order('nid'); }, <<<EOT
SELECT * FROM `{$p}_contents` `content` INNER JOIN `{$p}_nodes` `node` USING(`nid`) ORDER BY nid
EOT
			],

			[ function (Model $m) { return $m->order('nid', 1, 2, 3); }, <<<EOT
SELECT * FROM `{$p}_contents` `content` INNER JOIN `{$p}_nodes` `node` USING(`nid`) ORDER BY FIELD(nid, '1', '2', '3')
EOT
			],

			[ function (Model $m) { return $m->limit(5); }, <<<EOT
SELECT * FROM `{$p}_contents` `content` INNER JOIN `{$p}_nodes` `node` USING(`nid`) LIMIT 5
EOT
			],

			[ function (Model $m) { return $m->limit(5, 10); }, <<<EOT
SELECT * FROM `{$p}_contents` `content` INNER JOIN `{$p}_nodes` `node` USING(`nid`) LIMIT 5, 10
EOT
			],

			[ function (Model $m) { return $m->offset(5); }, <<<EOT
SELECT * FROM `{$p}_contents` `content` INNER JOIN `{$p}_nodes` `node` USING(`nid`) LIMIT 5, $l
EOT
			]
		];
	}

	public function test_activerecord_cache()
	{
		$model_id = 't' . uniqid();

		$models = new ModelCollection($this->connections, [

			$model_id => [

				Model::SCHEMA => [

					'id' => 'serial',
					'name' => 'varchar'

				]
			]
		]);

		$models->install();
		$model = $models[$model_id];

		foreach ([ 'one', 'two', 'three', 'four' ] as $value)
		{
			$model->save([ 'name' => $value ]);
		}

		$activerecord_cache = $model->activerecord_cache;

		$this->assertInstanceOf(ActiveRecordCache::class, $activerecord_cache);

		for ($i = 1 ; $i < 5 ; $i++)
		{
			$records[$i] = $model[$i];
		}

		for ($i = 1 ; $i < 5 ; $i++)
		{
			$this->assertSame($records[$i], $activerecord_cache->retrieve($i));
			$this->assertSame($records[$i], $model[$i]);
		}

		$activerecord_cache->clear();

		for ($i = 1 ; $i < 5 ; $i++)
		{
			$this->assertNull($activerecord_cache->retrieve($i));
			$this->assertNotSame($records[$i], $model[$i]);
		}

		#
		# A deleted record must not be available in the cache.
		#

		$records[1]->delete();
		$this->assertNull($activerecord_cache->retrieve(1));
		$this->setExpectedException(RecordNotFound::class);
		$model[1];
	}
}
