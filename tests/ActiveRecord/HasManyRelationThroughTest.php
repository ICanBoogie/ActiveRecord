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

use ICanBoogie\Acme\HasMany\Patient;
use ICanBoogie\Acme\HasMany\Physician;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Fixtures;

use function assert;

final class HasManyRelationThroughTest extends TestCase
{
    /**
     * @var Model<int, Physician>
     */
    private Model $physicians;

    protected function setUp(): void
    {
        [ , $models ] = Fixtures::only_models([ 'physicians', 'appointments', 'patients' ]);

        /*
         * NOTE: Relation and the prototype method are only setup when a model is loaded.
         */

        /** @phpstan-ignore-next-line*/
        $this->physicians = $models->model_for_id('physicians');
        $models->model_for_id('patients');
    }

    public function test_through_is_set(): void
    {
        $r = $this->physicians->relations;

        $ra = $r['appointments'];
        assert($ra instanceof HasManyRelation);
        $this->assertInstanceOf(HasManyRelation::class, $ra);
        $this->assertEquals('appointments', $ra->as);
        $this->assertEquals('ph_id', $ra->local_key);
        $this->assertEquals('physician_id', $ra->foreign_key);
        $this->assertNull($ra->through);

        $rp = $r['patients'];
        assert($rp instanceof HasManyRelation);
        $this->assertInstanceOf(HasManyRelation::class, $rp);
        $this->assertEquals('patients', $rp->as);
        $this->assertEquals('ph_id', $rp->local_key);
        $this->assertEquals('pa_id', $rp->foreign_key);
        $this->assertEquals('appointments', $rp->through);
    }

    public function test_physician_has_many_appointments(): void
    {
        $physician = new Physician();
        $physician->ph_id = 123;

        $query = $physician->appointments;

        $this->assertEquals(
            "SELECT * FROM `appointments` `appointment` WHERE (`physician_id` = ?)",
            (string)$query
        );

        $this->assertEquals(
            [ $physician->ph_id ],
            $query->args
        );
    }

    public function test_patient_has_many_appointments(): void
    {
        $patient = new Patient();
        $patient->pa_id = 123;

        $query = $patient->appointments;

        $this->assertEquals(
            "SELECT * FROM `appointments` `appointment` WHERE (`patient_id` = ?)",
            (string)$query
        );

        $this->assertEquals(
            [ $patient->pa_id ],
            $query->args
        );
    }

    public function test_physician_has_many_patients_though_appointments(): void
    {
        $physician = new Physician();
        $physician->ph_id = 123;

        $query = $physician->patients;

        $this->assertEquals(
            "SELECT `patient`.* FROM `patients` `patient` INNER JOIN `appointments` ON `appointments`.patient_id = `patient`.pa_id INNER JOIN `physicians` `physician` ON `appointments`.physician_id = `physician`.ph_id WHERE (`physician`.ph_id = ?)",
            (string)$query
        );

        $this->assertEquals(
            [ $physician->ph_id ],
            $query->args
        );
    }

    public function test_patient_has_many_physicians_though_appointments(): void
    {
        $patient = new Patient();
        $patient->pa_id = 123;

        $query = $patient->physicians;

        assert($query instanceof Query);

        $this->assertEquals(
            "SELECT `physician`.* FROM `physicians` `physician` INNER JOIN `appointments` ON `appointments`.physician_id = `physician`.ph_id INNER JOIN `patients` `patient` ON `appointments`.patient_id = `patient`.pa_id WHERE (`patient`.pa_id = ?)",
            (string)$query
        );

        $this->assertEquals(
            [ $patient->pa_id ],
            $query->args
        );
    }
}
