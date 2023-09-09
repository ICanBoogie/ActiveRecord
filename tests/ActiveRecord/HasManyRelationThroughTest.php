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

use ICanBoogie\ActiveRecord\HasManyRelation;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\HasMany\Appointment;
use Test\ICanBoogie\Acme\HasMany\AppointmentModel;
use Test\ICanBoogie\Acme\HasMany\Patient;
use Test\ICanBoogie\Acme\HasMany\PatientModel;
use Test\ICanBoogie\Acme\HasMany\Physician;
use Test\ICanBoogie\Acme\HasMany\PhysicianModel;
use Test\ICanBoogie\Fixtures;

use function array_column;
use function assert;

final class HasManyRelationThroughTest extends TestCase
{
    /**
     * @var Model<int, Physician>
     */
    private Model $physicians;

    /**
     * @var Model<int, Patient>
     */
    private Model $patients;

    /**
     * @var Model<int, Appointment>
     */
    private Model $appointments;

    protected function setUp(): void
    {
        [ , $models ] = Fixtures::only_models([ 'physicians', 'appointments', 'patients' ]);

        /*
         * NOTE: Relation and the prototype method are only setup when a model is loaded.
         */

        $this->physicians = $models->model_for_class(PhysicianModel::class);
        $this->patients = $models->model_for_class(PatientModel::class);
        $this->appointments = $models->model_for_class(AppointmentModel::class);
    }

    public function test_through_is_set(): void
    {
        $r = $this->physicians->relations;

        $this->assertArrayHasKey('appointments', $r);
        $this->assertArrayHasKey('patients', $r);

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
        $this->assertEquals(AppointmentModel::class, $rp->through);
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

    public function test_integration(): void
    {
        $this->physicians->install();
        $this->patients->install();
        $this->appointments->install();

        $patient_1 = new Patient($this->patients);
        $patient_1->name = "Patient 1";
        $patient_1->save();
        $patient_2 = new Patient($this->patients);
        $patient_2->name = "Patient 2";
        $patient_2->save();

        $physician_1 = new Physician($this->physicians);
        $physician_1->name = "Physician 1";
        $physician_1->save();
        $physician_2 = new Physician($this->physicians);
        $physician_2->name = "Physician 2";
        $physician_2->save();

        $appointment = new Appointment($this->appointments);
        $appointment->patient_id = $patient_1->pa_id;
        $appointment->physician_id = $physician_1->ph_id;
        $appointment->appointment_date = '2023-06-06';
        $appointment->save();

        $this->assertEquals([ "Physician 1" ], array_column($patient_1->physicians->all, 'name'));
        $this->assertEquals([ "Patient 1" ], array_column($physician_1->patients->all, 'name'));
        $this->assertEquals($physician_1, $appointment->physician);
        $this->assertEquals($patient_1, $appointment->patient);
    }
}
