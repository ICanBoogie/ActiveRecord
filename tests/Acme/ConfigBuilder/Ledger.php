<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;

/**
 * A record without schema attributes, its schema is defined with `schema_builder:`.
 */
class Ledger extends ActiveRecord
{
    public int $ledger_id;
}
