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
 * Returns a given model.
 *
 * @param string $id Model identifier.
 *
 * @return Model
 *
 * @throws ModelNotDefined if the model cannot be found.
 */
function get_model(string $id): Model
{
	return ModelProvider::provide($id);
}

/**
 * Extract the charset and collate from a charset/collate union.
 *
 * @param string $charset_and_collate
 *
 * @return array
 */
function extract_charset_and_collate(string $charset_and_collate): array
{
	[ $charset, $collate ] = \explode('/', $charset_and_collate) + [ 1 => 'general_ci'];

	return [ $charset, "{$charset}_{$collate}" ];
}
