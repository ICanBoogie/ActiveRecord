<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\HasManyRelation;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\RelationCollection;
use ICanBoogie\ActiveRecord\RelationNotDefined;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleModel;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\CommentModel;
use Test\ICanBoogie\Fixtures;

final class HasManyRelationTest extends TestCase
{
    private ArticleModel $articles;
    private CommentModel $comments;

    protected function setUp(): void
    {
        [ , $models ] = Fixtures::only_models([ 'nodes', 'articles', 'comments' ]);

        $models->install();
        $this->articles = $articles = $models->model_for_record(Article::class);

        for ($i = 1; $i < 4; $i++) {
            $articles->save([

                'title' => "Article $i",
                'body' => "Madonna",

            ]);
        }

        $this->comments = $comments = $models->model_for_record(Comment::class);

        for ($i = 1; $i < 13; $i++) {
            $comments->save([

                'nid' => ($i % 3) ?: 3,
                'body' => "Comment $i",

            ]);
        }
    }

    public function test_relations(): void
    {
        $relations = $this->articles->relations;
        $this->assertInstanceOf(RelationCollection::class, $relations);

        $relation = $relations['comments'];
        $this->assertInstanceOf(HasManyRelation::class, $relation);
        $this->assertSame('comments', $relation->as);
        $this->assertSame($this->articles, $relation->owner);
        $this->assertSame(Comment::class, $relation->related);
        $this->assertSame('nid', $relation->local_key);
        $this->assertSame('nid', $relation->foreign_key);
    }

    public function test_undefined_relation(): void
    {
        $this->expectException(RelationNotDefined::class);
        $this->articles->relations['undefined_relation'];
    }

    public function test_getter(): void
    {
        $article = $this->articles[1];
        $article_comments = $article->comments;

        $this->assertInstanceOf(Query::class, $article_comments);
        $this->assertSame($this->comments, $article_comments->model);
    }

    public function test_association_auto(): void
    {
        $comments = $this->articles[1]->comments->all();

        $this->assertCount(4, $comments);
        $this->assertEquals("Comment 1", $comments[0]->body);
        $this->assertEquals("Comment 4", $comments[1]->body);
        $this->assertEquals("Comment 7", $comments[2]->body);
        $this->assertEquals("Comment 10", $comments[3]->body);
    }

    public function test_association_as(): void
    {
        $article = $this->articles[1];
        $comments = $article->article_comments;

        $this->assertInstanceOf(Query::class, $comments);
        $this->assertSame($this->comments, $comments->model);
    }
}
