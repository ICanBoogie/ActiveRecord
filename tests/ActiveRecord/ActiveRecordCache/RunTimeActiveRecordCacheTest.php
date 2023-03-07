<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\ActiveRecordCache;

use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Fixtures;

final class RunTimeActiveRecordCacheTest extends TestCase
{
    public function test_cache(): void
    {
        [ , $models ] = Fixtures::only_models([ 'nodes', 'articles' ]);

        $model = $models->model_for_id('articles');
        $primary = $model->primary;
        $key = 123;

        $record = new Article($model);
        $record->$primary = $key;

        $this->assertSame($primary, $model->primary);

        $cache = new RuntimeActiveRecordCache($model);
        $cache->store($record);
        $this->assertSame($record, $cache->retrieve($key));
        $cache->store($record);
        $this->assertSame($record, $cache->retrieve($key));

        $cache->eliminate($key);
        $this->assertEmpty($cache->retrieve($key));

        $cache->store($record);
        $this->assertSame($record, $cache->retrieve($key));
        $cache->clear();
        $this->assertEmpty($cache->retrieve($key));

        foreach ($cache as $k => $r) {
            $this->assertSame($key, $k);
            $this->assertSame($record, $r);
        }
    }
}
