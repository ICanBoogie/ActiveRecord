<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord\Validate\ValidatorProvider;

use ICanBoogie\ActiveRecord\Validate\Validator\Unique;
use ICanBoogie\ActiveRecord\Validate\ValidatorProvider\ActiveRecordValidatorProvider;
use ICanBoogie\Validate\ValidatorProvider\BuiltinValidatorProvider;
use ICanBoogie\Validate\ValidatorProvider\ValidatorProviderCollection;
use PHPUnit\Framework\TestCase;

/**
 * @group validate
 * @small
 */
final class ActiveRecordValidatorProviderTest extends TestCase
{
    /**
     * @dataProvider provide_test_provider
     */
    public function test_provider(string $alias, string $class): void
    {
        $provider = new ValidatorProviderCollection([

            new ActiveRecordValidatorProvider(),
            new BuiltinValidatorProvider(),

        ]);

        $this->assertInstanceOf($class, $provider($alias));
    }

    /**
     * @return array<array{ string, class-string }>
     */
    public static function provide_test_provider(): array
    {
        return [

            [ 'unique', Unique::class ]

        ];
    }
}
