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

/**
 * A database statement.
 *
 * @property Connection $connection Connection associated with the statement.
 * @property-read array $all An array with the matching records.
 * @property-read array $pairs An array of key/value pairs, where _key_ is the value of the first
 * column and _value_ the value of the second column.
 * @property-read mixed $one The first matching record.
 * @property-read string $rc The value of the first column of the first row.
 */
class Statement extends \PDOStatement
{
	/**
	 * @uses get_connection
	 * @uses set_connection
	 * @uses get_once
	 * @uses get_all
	 * @uses get_rc
	 * @uses get_pairs
	 */
	use AccessorTrait;

	/**
	 * The database connection that created this statement.
	 *
	 * @var Connection
	 */
	private $connection;

	private function get_connection(): Connection
	{
		return $this->connection;
	}

	protected function set_connection(Connection $connection): void
	{
		$this->connection = $connection;
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
		if ($args && \is_array($args[0]))
		{
			$args = $args[0];
		}

		if ($this->execute($args) === false)
		{
			throw new StatementInvocationFailed($this, $args);
		}

		return $this;
	}

	/**
	 * Return the {@link queryString} property of the statement.
	 */
	public function __toString()
	{
		return $this->queryString;
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

		if (!empty($this->connection))
		{
			$this->connection->queries_count++;
		}

		try
		{
			$this->connection->profiling[] = [ $start, \microtime(true), $this->queryString . ' ' . \json_encode($args) ];

			return parent::execute($args);
		}
		catch (\PDOException $e)
		{
			throw new StatementNotValid([ $this, $args ], 500, $e);
		}
	}

	/**
	 * Set the fetch mode for the statement.
	 *
	 * @param mixed $mode
	 *
	 * @return Statement Return the instance.
	 *
	 * @throws UnableToSetFetchMode if the mode cannot be set.
	 *
	 * @see http://www.php.net/manual/en/pdostatement.setfetchmode.php
	 */
	public function mode(...$mode)
	{
		if (!$this->setFetchMode(...$mode))
		{
			throw new UnableToSetFetchMode($mode);
		}

		return $this;
	}

	/**
	 * Fetches the first row of the result set and closes the cursor.
	 *
	 * @param int $fetch_style
	 * @param int $cursor_orientation
	 * @param int $cursor_offset
	 *
	 * @return mixed
	 *
	 * @see PDOStatement::fetch()
	 */
	public function one($fetch_style = \PDO::FETCH_BOTH, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
	{
		$rc = $this->fetch(...func_get_args());

		$this->closeCursor();

		return $rc;
	}

	/**
	 * Alias for `one()`.
	 */
	protected function get_one()
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
	protected function get_rc()
	{
		$rc = $this->fetchColumn();

		$this->closeCursor();

		return $rc;
	}

	/**
	 * Alias for {@link \PDOStatement::fetchAll()}
	 *
	 * @param mixed $mode
	 *
	 * @return array
	 */
	public function all(...$mode)
	{
		return $this->fetchAll(...$mode);
	}

	/**
	 * Alias for `all()`.
	 */
	protected function get_all()
	{
		return $this->fetchAll();
	}

	/**
	 * Alias for `all(\PDO::FETCH_KEY_PAIR`).
	 *
	 * @return array An array of key/value pairs, where _key_ is the value of the first
	 * column and _value_ the value of the second column.
	 */
	protected function get_pairs()
	{
		return $this->fetchAll(\PDO::FETCH_KEY_PAIR);
	}
}
