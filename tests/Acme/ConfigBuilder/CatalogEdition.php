<?php

namespace Test\ICanBoogie\Acme\ConfigBuilder;

use ICanBoogie\ActiveRecord\Schema\Character;

/**
 * Extends {@see Catalog}.
 */
class CatalogEdition extends Catalog
{
    #[Character]
    public string $label;
}
