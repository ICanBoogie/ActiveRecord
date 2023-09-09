<?php

namespace ICanBoogie\ActiveRecord\Model;

use Attribute;
use ICanBoogie\ActiveRecord;
use LogicException;
use ReflectionClass;

use function count;
use function current;
use function sprintf;

#[Attribute(Attribute::TARGET_CLASS)]
final class Record
{
    /**
     * @param class-string<ActiveRecord\Model> $model_class
     *
     * @return class-string<ActiveRecord>
     *
     * @throws \ReflectionException
     */
    public static function resolve_activerecord_class(string $model_class): string
    {
        $records = (new ReflectionClass($model_class))
            ->getAttributes(ActiveRecord\Model\Record::class);

        if (count($records) === 0) {
            throw new LogicException(sprintf(
                "The '%s' attribute is not defined on class '%s'",
                ActiveRecord\Model\Record::class,
                $model_class,
            ));
        }

        if (count($records) > 1) {
            throw new LogicException(sprintf(
                "The '%s' attribute is defined more than once on class '%s'",
                ActiveRecord\Model\Record::class,
                $model_class,
            ));
        }

        /** @var Record $record */
        $record = current($records)->newInstance();
        $activerecord_class = $record->activerecord_class;

        ActiveRecord\Config\Assert::extends_activerecord(
            $activerecord_class,
            sprintf(
                "The activerecord class defined by the '%s' attribute on class '%s' must extend '%s'",
                self::class,
                $model_class,
                ActiveRecord::class,
            )
        );

        return $activerecord_class;
    }

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     */
    public function __construct(
        public readonly string $activerecord_class,
    ) {
    }
}
