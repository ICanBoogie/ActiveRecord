<?php

namespace ICanBoogie\ActiveRecord;

use PDO;
use PDOException;
use PDOStatement;

use function is_array;
use function json_encode;

/**
 * A database statement.
 */
final class Statement
{
    public function __construct(
        public readonly PDOStatement $pdo_statement,
        private readonly ConnectionTelemetry $telemetry,
    ) {
    }

    /**
     * Alias of {@see execute()}.
     *
     * The arguments can be provided as an array or a list of arguments:
     *
     *     $statement(1, 2, 3);
     *     $statement([ 1, 2, 3 ]);
     *
     * @return $this
     */
    public function __invoke(mixed ...$args): self
    {
        if ($args && is_array($args[0])) {
            $args = $args[0];
        }

        $this->execute($args); // @phpstan-ignore argument.type

        return $this;
    }

    /**
     * Return the {@see queryString} property of the statement.
     */
    public function __toString(): string
    {
        return $this->pdo_statement->queryString;
    }

    /**
     * Executes the statement.
     *
     * The connection queries count is incremented.
     *
     * @param array<scalar|null> $params
     *
     * @throws StatementNotValid when the execution of the statement fails.
     */
    public function execute(array $params = []): void
    {
        try {
            $this->telemetry->record_execute_duration(
                statement: $this->pdo_statement->queryString . ' ' . json_encode($params),
                closure: fn() => $this->pdo_statement->execute($params)
            );
        } catch (PDOException $e) {
            throw new StatementNotValid($this->pdo_statement->queryString, args: $params, original: $e);
        }
    }

    /**
     * Set the fetch mode for the statement.
     *
     * @return $this
     *
     * @throws UnableToSetFetchMode if the mode can't be set.
     *
     * @see PDOStatement::setFetchMode()
     */
    public function mode(int $mode, ?string $class_name = null, mixed ...$params): self
    {
        // @phpstan-ignore-next-line
        $this->pdo_statement->setFetchMode(...func_get_args())
            ?: throw new UnableToSetFetchMode(func_get_args());

        return $this;
    }

    /**
     * Equivalent to `mode(PDO::FETCH_ASSOC)`.
     */
    public self $as_assoc
    {
        get => $this->mode(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches the first row of the result set and closes the cursor.
     *
     * @see PDOStatement::fetch()
     */
    public function one(
        int $mode = PDO::FETCH_BOTH,
        int $cursor_orientation = PDO::FETCH_ORI_NEXT,
        int $cursor_offset = 0
    ): mixed {
        // @phpstan-ignore-next-line
        $rc = $this->pdo_statement->fetch(...func_get_args());

        $this->pdo_statement->closeCursor();

        return $rc;
    }

    /**
     * The first row of the result set (the cursor is closed).
     *
     * @see $one
     */
    public mixed $one
    {
        get => $this->one();
    }

    /**
     * The value of the first column of the first row.
     *
     * @see $rc
     */
    public int|string|false|null $rc
    {
        get {
            $rc = $this->pdo_statement->fetchColumn();

            $this->pdo_statement->closeCursor();

            return $rc;
        }
    }

    /**
     * Returns an array containing all the result-set rows.
     *
     * @return mixed[]
     *
     * @see PDOStatement::fetchAll()
     */
    public function all(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        // @phpstan-ignore-next-line
        return $this->pdo_statement->fetchAll(...func_get_args());
    }

    /**
     * @var array<scalar|null> $all
     *     An array with the matching records.
     *
     * @see $all
     */
    public array $all
    {
        get => $this->pdo_statement->fetchAll();
    }

    /**
     * @var array<string, scalar>
     *     Where _key_ is the value of the first column and _value_ the value of the second column.
     *
     * @see $pairs
     */
    public array $pairs
    {
        get => $this->pdo_statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
