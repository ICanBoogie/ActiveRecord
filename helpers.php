<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

/**
 * Returns the requested model.
 *
 * @param string $id Model identifier.
 *
 * @return Model
 */
function get_model($id)
{
	return Helpers::get_model($id);
}