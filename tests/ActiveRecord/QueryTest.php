<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use ICanBoogie\DateTime;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleQuery;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\Subscriber;
use Test\ICanBoogie\Acme\Update;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

use function get_object_vars;
use function gmdate;
use function rand;
use function time;

#[Group("db")]
final class QueryTest extends DbTestCase
{
    private const int N = 10;

    /**
     * @var Query<Node>
     */
    private Query $nodes;

    /**
     * @var Query<Article>
     */
    private Query $articles;

    /**
     * @var Query<Update>
     */
    private Query $updates;

    /**
     * @var Query<Subscriber>
     */
    private Query $subscribers;

    protected function setUp(): void
    {
        parent::setUp();

        $models = Fixtures::only_models('nodes', 'comments', 'articles', 'subscribers', 'updates');

        StaticModelProvider::set(fn() => $models);

        new ModelInstaller($models)->install();

        $articles = $models->model_for_record(Article::class);
        $this->nodes = $models->model_for_record(Node::class)->query();
        $this->articles = $articles->query();
        $this->updates = $models->model_for_record(Update::class)->query();
        $this->subscribers = $models->model_for_record(Subscriber::class)->query();

        for ($i = 1; $i < self::N + 1; $i++) {
            $articles->save([
                'title' => "TITLE $i",
                'body' => "BODY $i",
                'date' => gmdate('Y-m-d H:i:s', time() + 60 * rand(1, 3600)),
                'rating' => rand(0, 5),
            ]);
        }
    }

    private function quote_identifier(string $identifier): string
    {
        return $this->articles->model->connection->quote_identifier($identifier);
    }

    public function test_one(): void
    {
        $this->assertInstanceOf(Article::class, $this->articles->one);
    }

    public function test_all(): void
    {
        $all = $this->articles->all;

        $this->assertIsArray($all);
        $this->assertCount(self::N, $all);
    }

    public function test_rc(): void
    {
        // PostgreSQL is strict about types and rejects the string value used for the integer
        // column `nid` below, so the test is skipped on that engine.
        if ($this->articles->model->connection->driver_name === 'pgsql') {
            $this->markTestSkipped("PostgreSQL rejects a string value for the integer column 'nid'");
        }

        $actual = $this->articles->select('title')->rc;
        $this->assertEquals("TITLE 1", $actual);

        $actual = $this->articles->select('nid')->rc;
        $this->assertEquals("1", $actual);

        $actual = $this->articles->where([ 'nid' => 'foo' ])->rc;
        $this->assertFalse($actual);
    }

    public function test_order(): void
    {
        $actual = $this->articles->order('title ASC, rating DESC');

        $this->assertStringEndsWith('ORDER BY title ASC, rating DESC', (string)$actual);
    }

    public function test_order_expand_minus(): void
    {
        $actual = $this->articles->order('title ASC, -rating');

        $this->assertStringEndsWith('ORDER BY title ASC, rating DESC', (string)$actual);

        $actual = $this->articles->order('title ASC, -rating_underscored');

        $this->assertStringEndsWith('ORDER BY title ASC, rating_underscored DESC', (string)$actual);
    }

    public function test_order_by_field(): void
    {
        $m = $this->nodes;

        $q = $m->order('nid', [ 1, 2, 3 ]);
        $this->assertStringEndsWith("ORDER BY FIELD(nid, '1', '2', '3')", (string)$q);

        $q = $m->order('nid', 1, 2, 3);
        $this->assertStringEndsWith("ORDER BY FIELD(nid, '1', '2', '3')", (string)$q);
    }

    public function test_conditions(): void
    {
        $query = $this->articles;
        $quote = $this->quote_identifier(...);

        $query->where([ 'title' => 'madonna' ])
            ->and([ 'rating' => 2 ])
            ->and('YEAR(date) = ?', 1958);

        $this->assertSame([
            "(" . $quote('title') . " = ?)",
            "(" . $quote('rating') . " = ?)",
            "(YEAR(date) = ?)"
        ], $query->conditions);

        $this->assertSame([
            "madonna",
            2,
            1958
        ], $query->conditions_args);
    }

    public function test_join_with_expression(): void
    {
        $query = $this->updates->join(expression: "INNER JOIN madonna USING(madonna_id)");

        $this->assertEquals(
            [ "INNER JOIN madonna USING(madonna_id)" ],
            $query->joins
        );
    }

    /**
     * Without a select, only the columns of the record's table and its parents' tables are selected.
     */
    public function test_select_defaults_to_record_columns(): void
    {
        $quote = $this->quote_identifier(...);

        $this->assertEquals(
            'SELECT ' . $quote('article') . '.*, ' . $quote('node') . '.* FROM ' . $quote('articles') . ' ' . $quote('article')
            . ' INNER JOIN ' . $quote('nodes') . ' ' . $quote('node') . ' USING(' . $quote('nid') . ')',
            (string) $this->articles
        );
    }

    /**
     * The columns of joined tables don't leak into the record, even when they share a name with one of
     * its columns: `comments.body` must not overwrite `articles.body`.
     */
    public function test_join_with_model_only_hydrates_record_columns(): void
    {
        $this->articles->model->models->model_for_record(Comment::class)->save([
            'nid' => 1,
            'body' => "COMMENT BODY",
        ]);

        $article = $this->articles
            ->join(record: Comment::class)
            ->where([ 'nid' => 1 ])
            ->one;

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals("TITLE 1", $article->title);
        $this->assertEquals("BODY 1", $article->body);
        $this->assertArrayNotHasKey('comment_id', get_object_vars($article));
    }

    /**
     * An explicit select, even `*`, returns arrays.
     */
    public function test_explicit_select_returns_arrays(): void
    {
        $this->assertIsArray($this->articles->select('*')->one);
        $this->assertIsArray($this->articles->select('title')->one);
    }

    public function test_join_with_query(): void
    {
        $updates = $this->updates;
        $subscribers = $this->subscribers;
        $quote = $this->quote_identifier(...);

        $update_query = $updates
            ->select('subscriber_id, updated_at, update_hash')
            ->order('updated_at DESC');

        $subscriber_query = $subscribers
            ->join(query: $update_query, on: 'subscriber_id')
            ->group($quote('subscriber') . '.subscriber_id');

        $this->assertEquals(
            [ 'INNER JOIN(SELECT subscriber_id, updated_at, update_hash FROM ' . $quote('updates') . ' ' . $quote('update') . ' ORDER BY updated_at DESC) ' . $quote('update') . ' USING(' . $quote('subscriber_id') . ')' ],
            $subscriber_query->joins
        );
        $this->assertEquals(
            'SELECT ' . $quote('subscriber') . '.* FROM ' . $quote('subscribers') . ' ' . $quote('subscriber') . ' INNER JOIN(SELECT subscriber_id, updated_at, update_hash FROM ' . $quote('updates') . ' ' . $quote('update') . ' ORDER BY updated_at DESC) ' . $quote('update') . ' USING(' . $quote('subscriber_id') . ') GROUP BY ' . $quote('subscriber') . '.subscriber_id',
            (string)$subscriber_query
        );
    }

    public function test_join_with_query_with_args(): void
    {
        $updates = $this->updates;
        $subscribers = $this->subscribers;
        $now = DateTime::now();

        $update_query = $updates
            ->select('subscriber_id, updated_at, update_hash')
            ->where('updated_at < ?', $now)
            ->order('updated_at DESC');

        $subscriber_query = $subscribers
            ->join(query: $update_query, on: 'subscriber_id')
            ->where([ 'email' => 'person@example.com' ]);

        $this->assertSame([ $now->utc->as_db ], $subscriber_query->joins_args);
        $this->assertSame([ 'person@example.com' ], $subscriber_query->conditions_args);
        $this->assertSame([ $now->utc->as_db, 'person@example.com' ], $subscriber_query->args);
    }

    public function test_join_with_model(): void
    {
        $q1 = clone $this->updates;
        $q2 = clone $this->updates;
        $q3 = clone $this->updates;
        $quote = $this->quote_identifier(...);

        $this->assertEquals(
            'SELECT update_id, email FROM ' . $quote('updates') . ' ' . $quote('update') . ' INNER JOIN ' . $quote('subscribers') . ' AS ' . $quote('subscriber') . ' USING(' . $quote('subscriber_id') . ')',
            (string)$q1->select('update_id, email')->join(record: Subscriber::class)
        );

        $this->assertEquals(
            'SELECT update_id, email FROM ' . $quote('updates') . ' ' . $quote('update') . ' INNER JOIN ' . $quote('subscribers') . ' AS ' . $quote('sub') . ' USING(' . $quote('subscriber_id') . ')',
            (string)$q2->select('update_id, email')->join(record: Subscriber::class, as: 'sub')
        );

        $this->assertEquals(
            'SELECT update_id, email FROM ' . $quote('updates') . ' ' . $quote('update') . ' LEFT JOIN ' . $quote('subscribers') . ' AS ' . $quote('sub') . ' USING(' . $quote('subscriber_id') . ')',
            (string)$q3->select('update_id, email')->join(record: Subscriber::class, mode: 'LEFT', as: 'sub')
        );
    }

    public function test_query_extension(): void
    {
        $query = clone $this->articles;

        $this->assertInstanceOf(ArticleQuery::class, $query);

        $this->assertStringEndsWith('ORDER BY date DESC', (string)$query->ordered);
        $this->assertStringEndsWith('ORDER BY date ASC', (string)$query->ordered(1));
    }

    public function test_iterator(): void
    {
        // Need to start from zero.
        $this->nodes->model->truncate(reset_autoincrement: true);

        for ($i = 1; $i < 101; ++$i) {
            $node = new Node();
            $node->title = "node $i";
            $node->save();
        }

        $i = 31;
        $c = 0;

        foreach (Node::query()->batch_size(10)->skip(31) as $node) {
            $c++;
            $i++;
            $this->assertEquals("node $i", $node->title);
        }

        $this->assertEquals(100 - 31, $c);
    }
}
