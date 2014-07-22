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

use ICanBoogie\ActiveRecord\RelationHasManyTest\Article;
use ICanBoogie\ActiveRecord\RelationHasManyTest\Comment;

class RelationHasManyTest extends \PHPUnit_Framework_TestCase
{
	static private $connection;
	static private $articles;
	static private $comments;

	static public function setupBeforeClass()
	{
		self::$connection = new Connection('sqlite::memory:');

		self::$comments = new Model([

			Model::ACTIVERECORD_CLASS => __CLASS__ . '\Comment',
			Model::CONNECTION => self::$connection,
			Model::ID => 'comments',
			Model::NAME => 'comments',
			Model::SCHEMA => [

				'fields' => [

					'comment_id' => 'serial',
					'article_id' => 'foreign',
					'body' => 'text'

				]

			]

		]);

		self::$articles = new Model([

			Model::ACTIVERECORD_CLASS => __CLASS__ . '\Article',
			Model::CONNECTION => self::$connection,
			Model::HAS_MANY => self::$comments,
			Model::ID => 'articles',
			Model::NAME => 'articles',
			Model::SCHEMA => [

				'fields' => [

					'article_id' => 'serial',
					'title' => 'varchar'

				]

			]

		]);

		self::$articles->install();

		for ($i = 1 ; $i < 4 ; $i++)
		{
			self::$articles->save([

				'title' => "Article $i"

			]);
		}

		self::$comments->install();

		for ($i = 1 ; $i < 13 ; $i++)
		{
			self::$comments->save([

				'article_id' => ($i - 1) % 3,
				'body' => "Comment $i"

			]);
		}
	}

	public function test_getter()
	{
		$article = self::$articles[1];
		$article_comments = $article->comments;

		$this->assertInstanceOf('ICanBoogie\ActiveRecord\Query', $article_comments);
		$this->assertSame(self::$comments, $article_comments->model);
	}

	public function test_getter_as()
	{
		$articles = self::$articles;
		$articles->has_many(self::$comments, [ 'as' => 'article_comments' ]);
		$article = self::$articles[1];
		$article_comments = $article->article_comments;

		$this->assertInstanceOf('ICanBoogie\ActiveRecord\Query', $article_comments);
		$this->assertSame(self::$comments, $article_comments->model);
	}
}

namespace ICanBoogie\ActiveRecord\RelationHasManyTest;

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