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
 * @property-read array $all An array with the matching records.
 * @property-read array $pairs An array of key/value pairs, where _key_ is the value of the first
 * column and _value_ the value of the second column.
 * @property-read mixed $one The first matching record.
 * @property-read string $rc The value of the first column of the first row.
 */
class Statement extends \PDOStatement
{
	use AccessorTrait;

	/**
	 * The database connection that created this statement.
	 *
	 * @var Connection
	 */
	public $connection;

	/**
	 * Alias of {@link execute()}.
	 *
	 * The arguments can be provided as an array or a list of arguments:
	 *
	 *     $statement(1, 2, 3);
	 *     $statement([ 1, 2, 3 ]);
	 */
	public function __invoke()
	{
		$args = func_get_args();

		if ($args && is_array($args[0]))
		{
			$args = $args[0];
		}

		return $this->execute($args);
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
		$start = microtime(true);

		if (!empty($this->connection))
		{
			$this->connection->queries_count++;
		}

		try
		{
			$this->connection->profiling[] = [ $start, microtime(true), $this->queryString . ' ' . json_encode($args) ];

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
	public function mode($mode)
	{
		$mode = func_get_args();

		if (!call_user_func_array([ $this, 'setFetchMode' ], $mode))
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
	public function fetchAndClose($fetch_style = \PDO::FETCH_BOTH, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
	{
		$args = func_get_args();
		$rc = call_user_func_array([ $this, 'fetch' ], $args);

		$this->closeCursor();

		return $rc;
	}

	/**
	 * Alias for `fetchAndClose()`.
	 */
	protected function get_one()
	{
		return $this->fetchAndClose();
	}

	/**
	 * Fetches a column of the first row of the result set and closes the cursor.
	 *
	 * @param int $column_number
	 *
	 * @return string
	 *
	 * @see PDOStatement::fetchColumn()
	 */
	public function fetchColumnAndClose($column_number = 0)
	{
		$rc = parent::fetchColumn($column_number);

		$this->closeCursor();

		return $rc;
	}

	/**
	 * Alias for `fetchColumnAndClose()`.
	 */
	protected function get_rc()
	{
		return $this->fetchColumnAndClose();
	}

	/**
	 * Alias for {@link \PDOStatement::fetchAll()}
	 */
	public function all($fetch_style=null, $column_index=null, array $ctor_args=null)
	{
		return call_user_func_array([ $this, 'fetchAll' ], func_get_args());
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
