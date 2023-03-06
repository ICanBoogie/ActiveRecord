<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConnectionOptions as Options;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    private string $id;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->id = 'db' . uniqid();

        $this->connection = new Connection('sqlite::memory:', null, null, [

            Options::ID => $this->id,
            Options::CHARSET_AND_COLLATE => 'ascii/bin',
            Options::TIMEZONE => '+02:30'

        ]);
    }

    public function test_get_id()
    {
        $this->assertSame($this->id, $this->connection->id);
    }

    public function test_get_charset()
    {
        $this->assertSame('ascii', $this->connection->charset);
    }

    public function test_get_collate()
    {
        $this->assertSame('ascii_bin', $this->connection->collate);
    }

    public function test_get_timezone()
    {
        $this->assertSame('+02:30', $this->connection->timezone);
    }

    public function test_quote_identifier(): void
    {
        $this->assertSame("`identifier`", $this->connection->quote_identifier('identifier'));
    }
}
