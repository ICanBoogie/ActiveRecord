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

use ICanBoogie\Accessor\AccessorTrait;
use PDO;
use PDOException;
use PDOStatement;

use function is_array;
use function json_encode;
use function microtime;

/**
 * A database statement.
 *
 * @property-read array $all An array with the matching records.
 * @property-read array $pairs An array of key/value pairs, where _key_ is the value of the first
 * column and _value_ the value of the second column.
 * @property-read mixed $one The first matching record.
 * @property-read string $rc The value of the first column of the first row.
 */
final class Statement
{
    /**
     * @uses get_once
     * @uses get_all
     * @uses get_rc
     * @uses get_pairs
     */
    use AccessorTrait;

    /**
     * @param PDOStatement<mixed> $pdo_statement
     */
    public function __construct(
        public readonly PDOStatement $pdo_statement,
        public readonly Connection $connection
    ) {
    }

    /**
     * Alias of {@link execute()}.
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

        if ($this->execute($args) === false) {
            throw new StatementInvocationFailed($this, $args);
        }

        return $this;
    }

    /**
     * Return the {@link queryString} property of the statement.
     */
    public function __toString()
    {
        return $this->pdo_statement->queryString;
    }

    /**
     * Executes the statement.
     *
     * The connection queries count is incremented.
     *
     * @param mixed[]|null $params
     *
     * @throws StatementNotValid when the execution of the statement fails.
     */
    public function execute(array $params = null): bool
    {
        $start = microtime(true);

        $this->connection->queries_count++;

        try {
            $this->connection->profiling[] = [
                $start,
                microtime(true),
                $this->pdo_statement->queryString . ' ' . json_encode($params),
            ];

            return $this->pdo_statement->execute($params);
        } catch (PDOException $e) {
            throw new StatementNotValid([ $this, $params ], 500, $e);
        }
    }

    /**
     * Set the fetch mode for the statement.
     *
     * @throws UnableToSetFetchMode if the mode cannot be set.
     *
     * @see http://www.php.net/manual/en/pdostatement.setfetchmode.php
     */
    public function mode(int $mode, string|object|null $className = null, ...$params): Statement
    {
        if (!$this->pdo_statement->setFetchMode($mode, $className, $params)) {
            throw new UnableToSetFetchMode($mode);
        }

        return $this;
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
        $rc = $this->pdo_statement->fetch(...func_get_args());

        $this->pdo_statement->closeCursor();

        return $rc;
    }

    /**
     * Alias for `one()`.
     */
    protected function get_one(): mixed
    {
        return $this->one();
    }

    /**
     * Fetches the first column of the first row of the result set and closes the cursor.
     *
     * @return string
     *
     * @see PDOStatement::fetchColumn()
     */
    protected function get_rc(): mixed
    {
        $rc = $this->pdo_statement->fetchColumn();

        $this->pdo_statement->closeCursor();

        return $rc;
    }

    /**
     * Alias for {@link \PDOStatement::fetchAll()}
     *
     * @param mixed $mode
     *
     * @return array
     */
    public function all(...$mode): array
    {
        return $this->pdo_statement->fetchAll(...$mode);
    }

    /**
     * Alias for `all()`.
     */
    protected function get_all(): array
    {
        return $this->pdo_statement->fetchAll();
    }

    /**
     * Alias for `all(\PDO::FETCH_KEY_PAIR`).
     *
     * @return array An array of key/value pairs, where _key_ is the value of the first
     * column and _value_ the value of the second column.
     */
    protected function get_pairs(): array
    {
        return $this->pdo_statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
