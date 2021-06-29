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
use PDOStatement;

/**
 * A database statement.
 *
 * @property-read Connection $connection Connection associated with the statement.
 * @property-read PDOStatement $pdo_statement The decorated PDO statement.
 * @property-read array $all An array with the matching records.
 * @property-read array $pairs An array of key/value pairs, where _key_ is the value of the first
 * column and _value_ the value of the second column.
 * @property-read mixed $one The first matching record.
 * @property-read string $rc The value of the first column of the first row.
 */
final class Statement
{
	/**
	 * @uses get_connection
	 * @uses get_pdo_statement
	 * @uses get_once
	 * @uses get_all
	 * @uses get_rc
	 * @uses get_pairs
	 */
	use AccessorTrait;

	public function __construct(
		private PDOStatement $statement,
		private Connection $connection
	) {

	}

	private function get_connection(): Connection
	{
		return $this->connection;
	}

	private function get_pdo_statement(): PDOStatement
	{
		return $this->statement;
	}

	/**
	 * Alias of {@link execute()}.
	 *
	 * The arguments can be provided as an array or a list of arguments:
	 *
	 *     $statement(1, 2, 3);
	 *     $statement([ 1, 2, 3 ]);
	 *
	 * @param array|mixed... $args
	 *
	 * @return $this
	 */
	public function __invoke(...$args): self
	{
		if ($args && \is_array($args[0])) {
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
		return $this->statement->queryString;
	}

	/**
	 * Executes the statement.
	 *
	 * The connection queries count is incremented.
	 *
	 * @inheritdoc
	 *
	 * @throws StatementNotValid when the execution of the statement fails.
	 */
	public function execute($args = [])
	{
		$start = \microtime(true);

		$this->connection->queries_count++;

		try {
			$this->connection->profiling[] = [
				$start,
				\microtime(true),
				$this->statement->queryString . ' ' . \json_encode($args),
			];

			return $this->statement->execute($args);
		} catch (\PDOException $e) {
			throw new StatementNotValid([ $this, $args ], 500, $e);
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
		if (!$this->statement->setFetchMode($mode, $className, $params)) {
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
		$rc = $this->statement->fetch(...func_get_args());

		$this->statement->closeCursor();

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
		$rc = $this->statement->fetchColumn();

		$this->statement->closeCursor();

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
		return $this->statement->fetchAll(...$mode);
	}

	/**
	 * Alias for `all()`.
	 */
	protected function get_all(): array
	{
		return $this->statement->fetchAll();
	}

	/**
	 * Alias for `all(\PDO::FETCH_KEY_PAIR`).
	 *
	 * @return array An array of key/value pairs, where _key_ is the value of the first
	 * column and _value_ the value of the second column.
	 */
	protected function get_pairs(): array
	{
		return $this->statement->fetchAll(PDO::FETCH_KEY_PAIR);
	}
}
