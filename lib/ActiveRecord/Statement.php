<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\Accessor\AccessorTrait;
use PDO;
use PDOException;
use PDOStatement;

use function is_array;
use function json_encode;

/**
 * A database statement.
 *
 * @see self::get_as_assoc()
 * @property-read $this $as_assoc
 *     Equivalent to `mode(PDO::FETCH_ASSOC)`.
 *
 * @see self::get_all()
 * @property-read array $all
 *     An array with the matching records.
 * @see self::get_pairs()
 * @property-read array $pairs
 *     An array of key/value pairs, where _key_ is the value of the first column and _value_ the value of the second
 *     column.
 * @see self::get_one()
 * @property-read mixed $one
 *     The first row of the result set (the cursor is closed).
 * @see self::get_rc()
 * @property-read int|string|false|null $rc
 *     The value of the first column of the first row.
 */
final class Statement
{
    use AccessorTrait;

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

        $this->execute($args);

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
     * @param mixed[] $params
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
     * @throws UnableToSetFetchMode if the mode cannot be set.
     *
     * @see PDOStatement::setFetchMode()
     */
    public function mode(int $mode, string $class_name = null, mixed ...$params): self
    {
        // @phpstan-ignore-next-line
        $this->pdo_statement->setFetchMode(...func_get_args())
            ?: throw new UnableToSetFetchMode(func_get_args());

        return $this;
    }

    private function get_as_assoc(): self
    {
        return $this->mode(PDO::FETCH_ASSOC);
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
     * @see $one
     */
    private function get_one(): mixed
    {
        return $this->one();
    }

    /**
     * Fetches the first column of the first row of the result set and closes the cursor.
     *
     * @see $rc
     */
    private function get_rc(): int|string|false|null
    {
        $rc = $this->pdo_statement->fetchColumn();

        $this->pdo_statement->closeCursor();

        return $rc;
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
     * @return mixed[]
     *
     * @see $all
     */
    private function get_all(): array
    {
        return $this->pdo_statement->fetchAll();
    }

    /**
     * @return array<string, string>
     *     Where _key_ is the value of the first column and _value_ the value of the second column.
     *
     * @see $pairs
     */
    private function get_pairs(): array
    {
        return $this->pdo_statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
