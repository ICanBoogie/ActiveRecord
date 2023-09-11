<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConnectionNotDefined;
use ICanBoogie\ActiveRecord\ConnectionNotEstablished;
use ICanBoogie\ActiveRecord\Exception;
use ICanBoogie\ActiveRecord\ModelAlreadyInstantiated;
use ICanBoogie\ActiveRecord\ModelNotDefined;
use ICanBoogie\ActiveRecord\RecordNotFound;
use ICanBoogie\ActiveRecord\StatementNotValid;
use ICanBoogie\ActiveRecord\UnableToSetFetchMode;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ExceptionTest extends TestCase
{
    /**
     * @dataProvider provide_test_implementing
     *
     * @param array<mixed> $ctor_args
     */
    public function test_implementing(string $classname, array $ctor_args): void
    {
        $r = new ReflectionClass($classname);
        $exception = $r->newInstanceArgs($ctor_args);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    /**
     * @return array<array{ string, array<mixed> }>
     */
    public static function provide_test_implementing(): array
    {
        return [

            [ ConnectionNotDefined::class, [ 'connection-name' ] ],
            [ ConnectionNotEstablished::class, [ 'connection-name', 'message' ] ],

            [ RecordNotFound::class, [ "message", [] ] ],

            [ ModelNotDefined::class, [ 'model-name' ] ],
            [ ModelAlreadyInstantiated::class, [ 'model-name' ] ],

            [ StatementNotValid::class, [ 'statement' ] ],
            [ UnableToSetFetchMode::class, [ 'mode' ] ]

        ];
    }
}
