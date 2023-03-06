<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\Acme\Node;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleModel;

final class ConfigBuilderTest extends TestCase
{
    public function test_extends(): void
    {
        $config = (new ConfigBuilder())
            ->add_connection(
                id: Config::DEFAULT_CONNECTION_ID,
                dsn: 'sqlite::memory:',
            )
            ->add_model(
                id: 'nodes',
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('nid', primary: true)
                    ->add_varchar('title'),
                activerecord_class: Node::class,
            )
            ->add_model(
                id: 'articles',
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_text('body')
                    ->add_datetime('date'),
                activerecord_class: Article::class,
                extends: 'nodes',
                model_class: ArticleModel::class,
            )
            ->build();

        $schema = $config->models['articles'][Model::SCHEMA];

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertEquals('nid', $schema->primary);
        $this->assertFalse($schema['nid']->auto_increment);
    }
}
