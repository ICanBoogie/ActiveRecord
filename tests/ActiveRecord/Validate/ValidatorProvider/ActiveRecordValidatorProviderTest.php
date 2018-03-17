<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate\ValidatorProvider;

use ICanBoogie\ActiveRecord\Validate\Validator\Unique;

/**
 * @group validate
 * @small
 */
class ActiveRecordValidatorProviderTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @dataProvider provide_test_provider
	 *
	 * @param string $alias
	 * @param string $class
	 */
	public function test_provider($alias, $class)
	{
		$provider = new ActiveRecordValidatorProvider;

		$this->assertInstanceOf($class, $provider($alias));
	}

	/**
	 * @return array
	 */
	public function provide_test_provider()
	{
		return [

			[ 'unique', Unique::class ]

		];
	}
}
