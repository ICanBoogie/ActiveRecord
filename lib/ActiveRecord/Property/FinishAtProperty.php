<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;

/**
 * Implements a `finish_at` property.
 *
 * @see DateTimeProperty
 *
 * @property DateTime $finish_at
 *
 * @codeCoverageIgnore
 */
trait FinishAtProperty
{
    /**
     * The date and time at which the record was finish.
     *
     * @var mixed
     */
    private $finish_at;

    /**
     * Returns the date and time at which the record was finish.
     *
     * @return DateTime
     */
    protected function get_finish_at(): DateTime
    {
        return DateTimePropertySupport::get($this->finish_at);
    }

    /**
     * Sets the date and time at which the record was finish.
     *
     * @param mixed $datetime
     */
    protected function set_finish_at($datetime): void
    {
        DateTimePropertySupport::set($this->finish_at, $datetime);
    }
}
