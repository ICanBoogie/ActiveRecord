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
 * Version string of the ICanBoogie\ActiveRecord package.
 *
 * @var string
 */
const VERSION = '1.0.0 (2012-10-31)';

/**
 * The ROOT directory of the ICanBoogie\ActiveRecord package.
 *
 * @var string
 */
defined('ICanBoogie\ActiveRecord\ROOT') or define('ICanBoogie\ActiveRecord\ROOT', rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

/*
 * Helpers
 */
require_once ROOT . 'lib/helpers.php';