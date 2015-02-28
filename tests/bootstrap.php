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

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('ICanBoogie\ActiveRecord\ModelTest\\', __DIR__ . '/ModelTest');
$loader->addPsr4('ICanBoogie\ActiveRecord\QueryTest\\', __DIR__ . '/QueryTest');

date_default_timezone_set('Europe/Madrid');

Prototype::from(Model::class)['lazy_get_activerecord_cache'] = function(Model $model) {

	return new RunTimeActiveRecordCache($model);

};
