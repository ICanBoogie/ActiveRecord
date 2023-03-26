<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord\Validate;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Validate\ValidateActiveRecord;
use ICanBoogie\Validate\ValidationErrors;
use PHPUnit\Framework\TestCase;

/**
 * @group validate
 * @medium
 */
final class ValidateActiveRecordTest extends TestCase
{
    /**
     * @dataProvider provide_test_validate
     */
    public function test_validate(ActiveRecord $record, bool $should_validate): void
    {
        $validate = new ValidateActiveRecord();

        if ($should_validate) {
            $this->assertEmpty($validate($record));
        } else {
            $this->assertInstanceOf(ValidationErrors::class, $validate($record));
        }
    }

    /**
     * @return array<array{ object, bool }>
     */
    public static function provide_test_validate(): array
    {
        return [

            [ Sample::from(), false ],
            [ Sample::from([ 'email' => uniqid() ]), false ],
            [ Sample::from([ 'email' => 'person@domain.tld' ]), true ],
            [ SampleNoRules::from(), true ],
            [ SampleNoRules::from([ 'email' => uniqid() ]), true ],
            [ SampleNoRules::from([ 'email' => 'person@domain.tld' ]), true ],

        ];
    }
}
