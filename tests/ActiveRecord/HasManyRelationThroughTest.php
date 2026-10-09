<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\HasManyRelation;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\HasMany\Appointment;
use Test\ICanBoogie\Acme\HasMany\Patient;
use Test\ICanBoogie\Acme\HasMany\Physician;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

use function array_column;
use function assert;

#[Group("db")]
final class HasManyRelationThroughTest extends DbTestCase
{
    /**
     * @var Model<Physician>
     */
    private Model $physicians;

    /**
     * @var Model<Patient>
     */
    private Model $patients;

    /**
     * @var Model<Appointment>
     */
    private Model $appointments;

    protected function setUp(): void
    {
        $models = Fixtures::only_models('physicians', 'appointments', 'patients');

        /*
         * NOTE: Relation and the prototype method are only setup when a model is loaded.
         */

        $this->physicians = $models->model_for_record(Physician::class);
        $this->patients = $models->model_for_record(Patient::class);
        $this->appointments = $models->model_for_record(Appointment::class);

        StaticModelProvider::set(fn() => $models);
    }

    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    private function quote_identifier(string $identifier): string
    {
        return $this->physicians->connection->quote_identifier($identifier);
    }

    public function test_through_is_set(): void
    {
        $r = $this->physicians->relations;

        $this->assertTrue($r->has('appointments'));
        $this->assertTrue($r->has('patients'));

        $ra = $r->get('appointments');

        $this->assertInstanceOf(HasManyRelation::class, $ra);
        $this->assertEquals('appointments', $ra->as);
        $this->assertEquals('ph_id', $ra->local_key);
        $this->assertEquals('physician_id', $ra->foreign_key);
        $this->assertNull($ra->through);

        $rp = $r->get('patients');

        $this->assertInstanceOf(HasManyRelation::class, $rp);
        $this->assertEquals('patients', $rp->as);
        $this->assertEquals('ph_id', $rp->local_key);
        $this->assertEquals('pa_id', $rp->foreign_key);
        $this->assertEquals(Appointment::class, $rp->through);
    }

    public function test_physician_has_many_appointments(): void
    {
        $physician = new Physician();
        $physician->ph_id = 123;
        $quote = $this->quote_identifier(...);

        $query = $physician->appointments;

        $this->assertEquals(
            'SELECT * FROM ' . $quote('appointments') . ' ' . $quote('appointment') . ' WHERE (' . $quote('physician_id') . ' = ?)',
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
        $quote = $this->quote_identifier(...);

        $query = $patient->appointments;

        $this->assertEquals(
            'SELECT * FROM ' . $quote('appointments') . ' ' . $quote('appointment') . ' WHERE (' . $quote('patient_id') . ' = ?)',
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
        $quote = $this->quote_identifier(...);
        $query = $physician->patients;

        $this->assertEquals(
            'SELECT ' . $quote('patient') . '.* FROM ' . $quote('patients') . ' ' . $quote('patient')
            . ' INNER JOIN ' . $quote('appointments') . ' ON ' . $quote('appointments') . '.patient_id = ' . $quote('patient') . '.pa_id'
            . ' INNER JOIN ' . $quote('physicians') . ' ' . $quote('physician') . ' ON ' . $quote('appointments') . '.physician_id = ' . $quote('physician') . '.ph_id'
            . ' WHERE (' . $quote('physician') . '.ph_id = ?)',
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
        $quote = $this->quote_identifier(...);

        $query = $patient->physicians;

        assert($query instanceof Query);

        $this->assertEquals(
            'SELECT ' . $quote('physician') . '.* FROM ' . $quote('physicians') . ' ' . $quote('physician')
            . ' INNER JOIN ' . $quote('appointments') . ' ON ' . $quote('appointments') . '.physician_id = ' . $quote('physician') . '.ph_id'
            . ' INNER JOIN ' . $quote('patients') . ' ' . $quote('patient') . ' ON ' . $quote('appointments') . '.patient_id = ' . $quote('patient') . '.pa_id'
            . ' WHERE (' . $quote('patient') . '.pa_id = ?)',
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

        $patient_1 = new Patient();
        $patient_1->name = "Patient 1";
        $patient_1->save();
        $patient_2 = new Patient();
        $patient_2->name = "Patient 2";
        $patient_2->save();

        $physician_1 = new Physician();
        $physician_1->name = "Physician 1";
        $physician_1->save();
        $physician_2 = new Physician();
        $physician_2->name = "Physician 2";
        $physician_2->save();

        $appointment = new Appointment();
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
