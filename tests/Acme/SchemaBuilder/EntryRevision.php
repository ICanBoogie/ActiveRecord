<?php

namespace Test\ICanBoogie\Acme\SchemaBuilder;

use ICanBoogie\ActiveRecord\Schema\Integer;

/**
 * Only the columns declared by this class are used, not the ones of {@see Entry}.
 */
class EntryRevision extends Entry
{
    #[Integer(null: true)]
    public ?int $revision;
}
