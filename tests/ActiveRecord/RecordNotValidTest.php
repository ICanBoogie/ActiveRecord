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
use ICanBoogie\ActiveRecord\Validate\Validator\Unique;
use ICanBoogie\Validate\ValidationErrors;
use ICanBoogie\Validate\Validator\Email;

/**
 * @group validate
 * @small
 */
class RecordNotValidTest extends \PHPUnit\Framework\TestCase
{
	public function test_exception()
	{
		$record = new ActiveRecord($this->getMockBuilder(Model::class)->disableOriginalConstructor()->getMock());
		$errors = new ValidationErrors([

			'email' => [

				Email::DEFAULT_MESSAGE,
				Unique::DEFAULT_MESSAGE,

			]

		]);

		$exception = new RecordNotValid($record, $errors);

		$this->assertSame($record, $exception->record);
		$this->assertSame($errors, $exception->errors);

		$this->assertStringContainsString(Email::DEFAULT_MESSAGE, $exception->getMessage());
		$this->assertStringContainsString(Unique::DEFAULT_MESSAGE, $exception->getMessage());
	}
}
