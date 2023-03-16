<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate;

use ICanBoogie\ActiveRecord;
use ICanBoogie\Validate\ValidationErrors;

/**
 * @group validate
 * @medium
 */
class ValidateActiveRecordTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provide_test_validate
     *
     * @param ActiveRecord $record
     * @param bool $should_validate
     */
    public function test_validate($record, $should_validate)
    {
        $validate = new ValidateActiveRecord();

        if ($should_validate) {
            $this->assertEmpty($validate($record));
        } else {
            $this->assertInstanceOf(ValidationErrors::class, $validate($record));
        }
    }

    /**
     * @return array
     */
    public static function provide_test_validate()
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
