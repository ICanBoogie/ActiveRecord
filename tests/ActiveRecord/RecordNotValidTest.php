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

use ICanBoogie\Acme\Subscriber;
use ICanBoogie\ActiveRecord\Validate\Validator\Unique;
use ICanBoogie\Validate\ValidationErrors;
use ICanBoogie\Validate\Validator\Email;
use PHPUnit\Framework\TestCase;

/**
 * @group validate
 * @small
 */
final class RecordNotValidTest extends TestCase
{
    public function test_exception(): void
    {
        $model = $this->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'get_id'])
            ->getMock();
        $model->method('get_id')
            ->willReturn('acme');

        $record = new Subscriber();

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
