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

class SchemaColumnTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @dataProvider provide_test_to_string
	 *
	 * @param $options
	 * @param string $expected
	 */
	public function test_to_string($options, $expected)
	{
		$this->assertEquals($expected, (string) new SchemaColumn($options));
	}

	public function provide_test_to_string()
	{
		return [

			[ [ 'varchar' ]
			, "VARCHAR( 255 ) NOT NULL" ],
			[ [ 'type' => 'varchar' ]
			, "VARCHAR( 255 ) NOT NULL" ],
			[ [ 'varchar', 32 ]
			, "VARCHAR( 32 ) NOT NULL" ],
			[ [ 'type' => 'varchar', 'size' => 32 ]
			, "VARCHAR( 32 ) NOT NULL" ],
			[ [ 'type' => 'varchar', 'size' => 32, 'charset' => 'ascii/general_ci' ]
			, "VARCHAR( 32 ) CHARSET ascii COLLATE ascii_general_ci NOT NULL" ],

			[ [ 'integer', 'tiny' ]
			, "TINYINT NOT NULL" ],
			[ [ 'integer', 'small' ]
			, "SMALLINT NOT NULL" ],
			[ [ 'integer', 'big' ]
			, "BIGINT NOT NULL" ],
			[ [ 'integer', 'big', 'null' => true, 'unsigned' => true ]
			, "BIGINT UNSIGNED NULL" ],

			[ [ 'serial' ]
			, "BIGINT UNSIGNED NOT NULL AUTO_INCREMENT" ],

			[ [ 'varchar', 'indexed' => true ]
			, "VARCHAR( 255 ) NOT NULL" ],

			[ [ 'varchar', 'unique' => true ]
			, "VARCHAR( 255 ) NOT NULL" ]

		];
	}
}
