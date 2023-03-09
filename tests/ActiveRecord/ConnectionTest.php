<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    private string $id;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->id = 'db' . uniqid();

        $this->connection = new Connection(
            new ConnectionDefinition(
                id: $this->id,
                dsn:'sqlite::memory:',
                charset_and_collate: 'ascii/bin',
                time_zone: '+02:30',
            )
        );
    }

    public function test_get_id(): void
    {
        $this->assertSame($this->id, $this->connection->id);
    }

    public function test_get_charset(): void
    {
        $this->assertSame('ascii', $this->connection->charset);
    }

    public function test_get_collate(): void
    {
        $this->assertSame('ascii_bin', $this->connection->collate);
    }

    public function test_get_timezone(): void
    {
        $this->assertSame('+02:30', $this->connection->timezone);
    }

    public function test_quote_identifier(): void
    {
        $this->assertSame("`identifier`", $this->connection->quote_identifier('identifier'));
    }
}
