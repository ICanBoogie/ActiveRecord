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

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\ActiveRecordCache\RuntimeActiveRecordCache;
use ICanBoogie\Prototype;

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('ICanBoogie\\', __DIR__);

date_default_timezone_set('Europe/Madrid');

Prototype::configure([

	ActiveRecord::class => [

		'validate' => function(ActiveRecord $record) {

			static $validate;

			if (!$validate)
			{
				$validate = new ActiveRecord\Validate\ValidateActiveRecord;
			}

			return $validate($record);

		}

	],

	Model::class => [

		'lazy_get_activerecord_cache' => function(Model $model) {

			return new RuntimeActiveRecordCache($model);

		}

	]

]);
