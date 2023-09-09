<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ModelCollection;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleModel;
use Test\ICanBoogie\Acme\NodeModel;
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
            $this->models->model_for_class(NodeModel::class),
            $this->models->model_for_class(ArticleModel::class)->parent
        );
    }

    public function test_save(): void
    {
        $model = $this->models->model_for_class(ArticleModel::class);
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
