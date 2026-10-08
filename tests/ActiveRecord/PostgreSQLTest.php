<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\ConnectionNotEstablished;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\Postgres\Sample;
use Test\ICanBoogie\DbTestCase;

use function extension_loaded;
use function getenv;

#[Group("db")]
#[Group("pgsql")]
final class PostgreSQLTest extends DbTestCase
{
    private const DEFAULT_DSN = 'pgsql:host=127.0.0.1;dbname=postgres';
    private const DEFAULT_USERNAME = 'postgres';
    private const DEFAULT_PASSWORD = 'postgres';

    private Model $samples;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_pgsql')) {
            $this->markTestSkipped('The pdo_pgsql extension is not loaded');
        }

        $config = new ConfigBuilder()
            ->use_attributes()
            ->add_connection(
                id: Config::DEFAULT_CONNECTION_ID,
                dsn: getenv('ACTIVERECORD_DSN') ?: self::DEFAULT_DSN,
                username: getenv('ACTIVERECORD_USERNAME') ?: self::DEFAULT_USERNAME,
                password: getenv('ACTIVERECORD_PASSWORD') ?: self::DEFAULT_PASSWORD,
            )
            ->add_record(Sample::class)
            ->build();

        try {
            $connections = new ConnectionCollection($config->connections);
            $connection = $connections->connection_for_id(Config::DEFAULT_CONNECTION_ID);

            if ($connection->driver_name !== 'pgsql') {
                $this->markTestSkipped("Not a pgsql connection, got: {$connection->driver_name}");
            }

            $models = new ModelCollection($connections, $config->models);

            $models->uninstall();
            $models->install();

            $this->samples = $models->model_for_record(Sample::class);
        } catch (ConnectionNotEstablished $e) {
            $this->markTestSkipped("PostgreSQL is not available: {$e->getMessage()}");
        }
    }

    public function test_save_and_load(): void
    {
        $sample = $this->samples->new([
            'name' => 'one',
            'active' => true,
            'count' => 42,
        ]);
        $sample->save();

        $id = $sample->id;
        $this->assertIsInt($id);

        $reloaded = $this->samples->find($id);
        assert($reloaded instanceof Sample);

        $this->assertSame('one', $reloaded->name);
        $this->assertTrue($reloaded->active);
        $this->assertIsBool($reloaded->active);
        $this->assertSame(42, $reloaded->count);
        $this->assertIsInt($reloaded->count);
    }

    public function test_save_and_load_false(): void
    {
        $sample = $this->samples->new([
            'name' => 'two',
            'active' => false,
            'count' => 0,
        ]);
        $sample->save();

        $reloaded = $this->samples->find($sample->id);
        assert($reloaded instanceof Sample);

        $this->assertFalse($reloaded->active);
        $this->assertIsBool($reloaded->active);
        $this->assertSame(0, $reloaded->count);
    }

    public function test_query_by_boolean(): void
    {
        $this->samples->save([ 'name' => 'on', 'active' => true, 'count' => 1 ]);
        $this->samples->save([ 'name' => 'off', 'active' => false, 'count' => 2 ]);

        $active = $this->samples->where([ 'active' => true ])->all;
        $this->assertCount(1, $active);
        $this->assertSame('on', $active[0]->name);

        $inactive = $this->samples->where([ 'active' => false ])->all;
        $this->assertCount(1, $inactive);
        $this->assertSame('off', $inactive[0]->name);
    }
}
