<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord;

/**
 * A record without schema attributes, that belongs to a {@see Ledger}.
 */
class Receipt extends ActiveRecord
{
    public int $receipt_id;
    public int $ledger_id;
}
