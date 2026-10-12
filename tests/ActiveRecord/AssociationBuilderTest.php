<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\AssociationBuilder;
use ICanBoogie\ActiveRecord\Config\InvalidAssociation;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\ICanBoogie\Acme\ConfigBuilder\Publication;
use Test\ICanBoogie\Acme\ConfigBuilder\Reader;
use Test\ICanBoogie\Acme\ConfigBuilder\Subscription;

final class AssociationBuilderTest extends TestCase
{
    public function test_starts_with_the_associations_of_the_record(): void
    {
        $association = new AssociationBuilder(Reader::class)->build();

        $this->assertCount(2, $association->has_many);
        $this->assertSame(Subscription::class, $association->has_many[0]->associate);
        $this->assertSame(Publication::class, $association->has_many[1]->associate);
        $this->assertSame(Subscription::class, $association->has_many[1]->through);
    }

    public function test_starts_empty_without_record(): void
    {
        $this->assertSame([], new AssociationBuilder()->build()->has_many);
    }

    public function test_record_class_must_be_an_activerecord(): void
    {
        $this->expectException(LogicException::class);

        new AssociationBuilder(stdClass::class);
    }

    public function test_has_many_completes_the_attributes(): void
    {
        $association = new AssociationBuilder(Reader::class)
            ->has_many(Subscription::class, foreign_key: 'reader_id', as: 'other_subscriptions')
            ->build();

        $this->assertCount(3, $association->has_many);
    }

    public function test_duplicate_has_many_is_rejected(): void
    {
        $builder = new AssociationBuilder(Reader::class);

        try {
            $builder->has_many(Subscription::class);
            $this->fail("Expected InvalidAssociation");
        } catch (InvalidAssociation $e) {
            $this->assertSame(Reader::class, $e->record_class);
            $this->assertStringContainsString(Subscription::class, $e->getMessage());
            $this->assertStringContainsString("record `" . Reader::class . "`", $e->getMessage());
        }
    }

    public function test_associate_must_be_an_activerecord(): void
    {
        $this->expectException(InvalidAssociation::class);

        // @phpstan-ignore-next-line
        new AssociationBuilder()->has_many(stdClass::class);
    }

    public function test_through_must_be_an_activerecord(): void
    {
        $this->expectException(InvalidAssociation::class);

        // @phpstan-ignore-next-line
        new AssociationBuilder()->has_many(Subscription::class, through: stdClass::class);
    }

    public function test_empty_foreign_key_is_rejected(): void
    {
        $this->expectException(InvalidAssociation::class);

        // @phpstan-ignore-next-line
        new AssociationBuilder()->has_many(Subscription::class, foreign_key: '');
    }

    public function test_empty_as_is_rejected(): void
    {
        $this->expectException(InvalidAssociation::class);

        // @phpstan-ignore-next-line
        new AssociationBuilder()->has_many(Subscription::class, as: '');
    }
}
