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

class SchemaTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_test_primary
	 *
	 * @param $expected
	 * @param array $columns
	 */
	public function test_primary($expected, array $columns)
	{
		$schema = new Schema($columns);

		$this->assertSame($expected, $schema->primary);
	}

	public function provide_test_primary()
	{
		return [

			[ null, [

				'title' => 'varchar'

			] ],

			[ 'id', [

				'id' => 'serial',
				'title' => 'varchar'

			] ],

			[ [ 'nid', 'uid' ], [

				'nid' => [ 'foreign', 'primary' => true ],
				'uid' => [ 'foreign', 'primary' => true ],

			] ]

		];
	}

	/**
	 * @dataProvider provide_test_indexes
	 *
	 * @param array $expected
	 * @param array $columns
	 */
	public function test_indexes(array $expected, array $columns)
	{
		$schema = new Schema($columns);

		$this->assertSame($expected, $schema->indexes);
	}

	public function provide_test_indexes()
	{
		return [

			[ [ ], [

				'id' => 'serial',
				'title' => 'varchar'

			] ],

			[ [ 'uid' => [ 'uid' ] ], [

				'id' => 'serial',
				'uid' => 'foreign'

			] ],

			[ [ 'a' => [ 'a' ], 'b' => [ 'b' ] ], [

				'a' => [ 'varchar', 'indexed' => true ],
				'b' => [ 'varchar', 'indexed' => true ]

			] ],

			[ [ 'c_idx' => [ 'a', 'b' ] ], [

				'a' => [ 'varchar', 'indexed' => 'c_idx' ],
				'b' => [ 'varchar', 'indexed' => 'c_idx' ]

			] ],

			[ [ 'c_idx' => [ 'a', 'b' ], 'c' => [ 'c' ] ], [

				'a' => [ 'varchar', 'indexed' => 'c_idx' ],
				'b' => [ 'varchar', 'indexed' => 'c_idx' ],
				'c' => 'foreign'

			] ]

		];
	}

	/**
	 * @dataProvider provide_test_unique_indexes
	 *
	 * @param array $expected
	 * @param array $columns
	 */
	public function test_unique_indexes(array $expected, array $columns)
	{
		$schema = new Schema($columns);

		$this->assertSame($expected, $schema->unique_indexes);
	}

	public function provide_test_unique_indexes()
	{
		return [

			[ [ ], [

				'id' => 'serial',
				'uid' => 'foreign'

			] ],

			[ [ 'a' => [ 'a' ], 'b' => [ 'b' ] ], [

				'a' => [ 'varchar', 'unique' => true ],
				'b' => [ 'varchar', 'unique' => true ]

			] ],

			[ [ 'c_idx' => [ 'a', 'b' ] ], [

				'a' => [ 'varchar', 'unique' => 'c_idx' ],
				'b' => [ 'varchar', 'unique' => 'c_idx' ]

			] ],

			[ [ 'c_idx' => [ 'a', 'b' ], 'c' => [ 'c' ] ], [

				'a' => [ 'varchar', 'unique' => 'c_idx' ],
				'b' => [ 'varchar', 'unique' => 'c_idx' ],
				'c' => [ 'varchar', 'unique' => true ]

			] ]

		];
	}
}
