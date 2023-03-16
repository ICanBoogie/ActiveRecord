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

class ExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provide_test_implementing
     */
    public function test_implementing($classname, $ctor_args)
    {
        $r = new \ReflectionClass(__NAMESPACE__ . '\\' . $classname);
        $exception = $r->newInstanceArgs($ctor_args);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public static function provide_test_implementing()
    {
        return [

            [ 'ConnectionNotDefined', [ 'connection-name' ] ],
            [ 'ConnectionNotEstablished', [ 'connection-name', 'message' ] ],
            [ 'ConnectionAlreadyEstablished', [ 'connection-name' ] ],

            [ 'RecordNotFound', [ "message", [] ] ],

            [ 'ModelNotDefined', [ 'model-name' ] ],
            [ 'ModelAlreadyInstantiated', [ 'model-name' ] ],

            [ 'StatementNotValid', [ 'statement' ] ],
            [ 'UnableToSetFetchMode', [ 'mode' ] ]

        ];
    }
}
