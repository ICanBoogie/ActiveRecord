<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConnectionNotDefined;
use ICanBoogie\ActiveRecord\ConnectionNotEstablished;
use ICanBoogie\ActiveRecord\Exception;
use ICanBoogie\ActiveRecord\RecordNotFound;
use ICanBoogie\ActiveRecord\StatementNotValid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ExceptionTest extends TestCase
{
    /**
     *
     * @param array<mixed> $ctor_args
     */
    #[DataProvider('provide_test_implementing')]
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

            [ StatementNotValid::class, [ 'statement' ] ],

        ];
    }
}
