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

$hooks = __NAMESPACE__ . '\Hooks::';

return [

	'prototypes' => [

		'ICanBoogie\Core::lazy_get_connections' => $hooks . 'core_lazy_get_connections',
		'ICanBoogie\Core::lazy_get_db' => $hooks . 'core_lazy_get_db',
		__NAMESPACE__ . '\Model::lazy_get_activerecord_cache' => $hooks . 'model_lazy_get_activerecord_cache'

	]

];
