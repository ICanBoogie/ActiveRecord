<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ModelCollection;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Fixtures;

final class ModelExtendTest extends TestCase
{
    private ModelCollection $models;

    protected function setUp(): void
    {
        [ , $this->models ] = Fixtures::only_models([ 'nodes', 'articles', 'comments' ]);

        $this->models->install();
    }

    public function test_parent(): void
    {
        $this->assertSame(
            $this->models['nodes'],
            $this->models['articles']->parent
        );
    }

    public function test_save(): void
    {
        $model = $this->models['articles'];
        $nid = $model->save([
            'title' => "Title",
            'body' => "Body"
        ]);

        $this->assertEquals(1, $nid);

        $record = $model[$nid];

        assert($record instanceof Article);

        $this->assertEquals(1, $record->nid);
        $this->assertEquals("Title", $record->title);
        $this->assertEquals("Body", $record->body);
        $this->assertNotEmpty($record->date);
        $this->assertNull($record->rating);
    }
}
