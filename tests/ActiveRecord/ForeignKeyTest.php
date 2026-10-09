<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\ActiveRecord\StatementNotValid;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\Driver;
use Test\ICanBoogie\Acme\ForeignKey\Author;
use Test\ICanBoogie\Acme\ForeignKey\Book;
use Test\ICanBoogie\Acme\ForeignKey\Loan;
use Test\ICanBoogie\Acme\ForeignKey\Review;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

#[Group("db")]
final class ForeignKeyTest extends DbTestCase
{
    private ModelCollection $models;
    private Model $authors;
    private Model $books;
    private Model $reviews;
    private Model $loans;

    protected function setUp(): void
    {
        // Records are defined before the records they reference, install() must reorder them.
        $config = Fixtures::with_main_connection(new ConfigBuilder())
            ->use_attributes()
            ->add_record(Review::class)
            ->add_record(Loan::class)
            ->add_record(Book::class)
            ->add_record(Author::class)
            ->build();

        $this->models = new ModelCollection(new ConnectionCollection($config->connections), $config->models);
        $this->models->install();

        $this->authors = $this->models->model_for_record(Author::class);
        $this->books = $this->models->model_for_record(Book::class);
        $this->reviews = $this->models->model_for_record(Review::class);
        $this->loans = $this->models->model_for_record(Loan::class);
    }

    public function test_install_creates_referenced_tables_first(): void
    {
        $this->assertSame(
            [ Review::class => true, Loan::class => true, Book::class => true, Author::class => true ],
            $this->models->is_installed()
        );
    }

    public function test_reference_must_exist(): void
    {
        $this->expectException(StatementNotValid::class);

        $this->books->save([ 'author_id' => 123, 'title' => "Orphan" ]);
    }

    public function test_on_delete_cascade(): void
    {
        $author_id = $this->authors->save([ 'name' => "Ursula" ]);
        $other_id = $this->authors->save([ 'name' => "Octavia" ]);
        $this->books->save([ 'author_id' => $author_id, 'title' => "The Dispossessed" ]);
        $this->books->save([ 'author_id' => $author_id, 'title' => "The Lathe of Heaven" ]);
        $this->books->save([ 'author_id' => $other_id, 'title' => "Kindred" ]);

        $this->authors->delete($author_id);

        $this->assertSame([ "Kindred" ], $this->books->query()->select('title')->all(\PDO::FETCH_COLUMN));
    }

    public function test_on_delete_set_null(): void
    {
        $author_id = $this->authors->save([ 'name' => "Ursula" ]);
        $book_id = $this->books->save([ 'author_id' => $author_id, 'title' => "The Dispossessed" ]);
        $review_id = $this->reviews->save([ 'book_id' => $book_id, 'body' => "Ambiguous" ]);

        $this->books->delete($book_id);

        $review = $this->reviews->find($review_id);

        $this->assertNull($review->book_id);
        $this->assertSame("Ambiguous", $review->body);
    }

    public function test_on_delete_restrict(): void
    {
        $author_id = $this->authors->save([ 'name' => "Ursula" ]);
        $book_id = $this->books->save([ 'author_id' => $author_id, 'title' => "The Dispossessed" ]);
        $this->loans->save([ 'book_id' => $book_id, 'borrower' => "Shevek" ]);

        try {
            $this->books->delete($book_id);
            $this->fail("Expected StatementNotValid");
        } catch (StatementNotValid) {
            // expected
        }

        $this->assertTrue($this->books->query()->exists($book_id));
    }

    public function test_uninstall_drops_referencing_tables_first(): void
    {
        $this->models->uninstall();

        $this->assertSame(
            [ Review::class => false, Loan::class => false, Book::class => false, Author::class => false ],
            $this->models->is_installed()
        );
    }

    public function test_install_fails_on_cycle(): void
    {
        $config = Fixtures::with_main_connection(new ConfigBuilder())
            ->add_record(
                record_class: Brand::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('id', primary: true)
                    ->belongs_to('driver_id', Driver::class, null: true, on_delete: OnDelete::SetNull),
            )
            ->add_record(
                record_class: Driver::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('id', primary: true)
                    ->belongs_to('brand_id', Brand::class, on_delete: OnDelete::Cascade),
            )
            ->build();

        $models = new ModelCollection(new ConnectionCollection($config->connections), $config->models);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("foreign keys form a cycle: " . Brand::class . " -> " . Driver::class);

        $models->install();
    }
}
