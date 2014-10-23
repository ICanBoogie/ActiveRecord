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

use ICanBoogie\Prototype;

Prototype::from(__NAMESPACE__ . '\Model')['lazy_get_activerecord_cache'] = __NAMESPACE__ . '\Hooks::model_lazy_get_activerecord_cache';
