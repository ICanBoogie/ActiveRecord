<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

/**
 * Extends {@see Ledger}, its schema is defined with `schema_builder:`.
 */
class LedgerEntry extends Ledger
{
    public string $memo;
}
