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

use ICanBoogie\ActiveRecord\HasManyRelationTest\Article;
use ICanBoogie\ActiveRecord\HasManyRelationTest\Comment;

class HasManyRelationTest extends \PHPUnit_Framework_TestCase
{
	private $articles;
	private $comments;

	public function setUp()
	{
		$connections = new ConnectionCollection([

			'primary' => 'sqlite::memory:'

		]);

		$models = new ModelCollection($connections, [

			'comments' => [

				Model::ACTIVERECORD_CLASS => __CLASS__ . '\Comment',
				Model::ID => 'comments',
				Model::NAME => 'comments',
				Model::SCHEMA => [

					'comment_id' => 'serial',
					'article_id' => 'foreign',
					'body' => 'text'

				]
			],

			'articles' => [

				Model::ACTIVERECORD_CLASS => __CLASS__ . '\Article',
				Model::HAS_MANY => 'comments',
				Model::ID => 'articles',
				Model::NAME => 'articles',
				Model::SCHEMA => [

					'article_id' => 'serial',
					'title' => 'varchar'

				]
			]
		]);

		$models->install();
		$this->articles = $articles = $models['articles'];

		for ($i = 1 ; $i < 4 ; $i++)
		{
			$articles->save([

				'title' => "Article $i"

			]);
		}

		$this->comments = $comments = $models['comments'];

		for ($i = 1 ; $i < 13 ; $i++)
		{
			$comments->save([

				'article_id' => ($i - 1) % 3,
				'body' => "Comment $i"

			]);
		}
	}

	public function test_getters()
	{
		$relations = $this->articles->relations;
		$this->assertInstanceOf(RelationCollection::class, $relations);

		$relation = $relations['comments'];
		$this->assertInstanceOf(HasManyRelation::class, $relation);
		$this->assertSame('comments', $relation->as);
		$this->assertSame($this->articles, $relation->parent);
		$this->assertSame($this->comments, $relation->related);
		$this->assertSame($this->articles->primary, $relation->local_key);
		$this->assertSame($this->articles->primary, $relation->foreign_key);
	}

	/**
	 * @expectedException \ICanBoogie\ActiveRecord\RelationNotDefined
	 */
	public function test_undefined_relation()
	{
		$this->articles->relations['undefined_relation'];
	}

	public function test_getter()
	{
		$article = $this->articles[1];
		$article_comments = $article->comments;

		$this->assertInstanceOf(Query::class, $article_comments);
		$this->assertSame($this->comments, $article_comments->model);
	}

	public function test_getter_as()
	{
		$articles = $this->articles;
		$articles->has_many($this->comments, [ 'as' => 'article_comments' ]);
		$article = $this->articles[1];
		$article_comments = $article->article_comments;

		$this->assertInstanceOf(Query::class, $article_comments);
		$this->assertSame($this->comments, $article_comments->model);
	}
}

namespace ICanBoogie\ActiveRecord\HasManyRelationTest;

use ICanBoogie\ActiveRecord;

class Comment extends ActiveRecord
{
	public $comment_id;
	public $article_id;
	public $body;
}

class Article extends ActiveRecord
{
	public $article_id;
	public $title;
}
