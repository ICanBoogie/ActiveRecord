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

use ICanBoogie\PropertyNotDefined;

/**
 * A database statement.
 *
 * @property-read array $all An array with the matching records.
 * @property-read mixed $one The first matching record.
 * @property-read string $rc The value of the first column of the first row.
 */
class Statement extends \PDOStatement
{
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
	 * @throws PropertyNotDefined in attempt to get a property that is not defined.
	 */
	public function __get($property)
	{
		switch ($property)
		{
			case 'all': return $this->fetchAll();
			case 'one': return $this->fetchAndClose();
			case 'rc': return $this->fetchColumnAndClose();
		}

		throw new PropertyNotDefined([ $property, $this ]);
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
	 * @throws StatementInvalid when the execution of the statement fails.
	 */
	public function execute($args=[])
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
			throw new StatementInvalid([ $this, $args ], 500, $e);
		}
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
	public function fetchAndClose($fetch_style=\PDO::FETCH_BOTH, $cursor_orientation=\PDO::FETCH_ORI_NEXT, $cursor_offset=0)
	{
		$args = func_get_args();
		$rc = call_user_func_array([ $this, 'parent::fetch' ], $args);

		$this->closeCursor();

		return $rc;
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
	public function fetchColumnAndClose($column_number=0)
	{
		$rc = parent::fetchColumn($column_number);

		$this->closeCursor();

		return $rc;
	}

	/**
	 * Returns an array containing all of the result set rows (FETCH_LAZY supported)
	 *
	 * @param int $fetch_style
	 * @param mixed $fetch_argument
	 * @param array $ctor_args
	 *
	 * @return array
	 */
	public function fetchGroups($fetch_style, $fetch_argument=null, array $ctor_args=[])
	{
		$args = func_get_args();
		$rc = [];

		if($fetch_style === \PDO::FETCH_LAZY)
		{
			call_user_func_array([ $this, 'setFetchMode' ], $args);

			foreach($this as $row)
			{
				$rc[$row[0]][] = $row;
			}

			return $rc;
		}

		$args[0] = \PDO::FETCH_GROUP | $fetch_style;

		$rc = call_user_func_array([ $this, 'parent::fetchAll' ], $args);

		return $rc;
	}

	/**
	 * Set the fetch mode for the statement.
	 *
	 * @param mixed $mode
	 *
	 * @return Statement Return the instance.
	 *
	 * @throws ActiveRecordException if the mode cannot be set.
	 *
	 * @see http://www.php.net/manual/en/pdostatement.setfetchmode.php
	 */
	public function mode($mode)
	{
		$mode = func_get_args();

		if (!call_user_func_array([ $this, 'setFetchMode' ], $mode))
		{
			throw new ActiveRecordException("Unable to set fetch mode.");
		}

		return $this;
	}

	/**
	 * Alias for {@link \PDOStatement::fetchAll()}
	 */
	public function all($fetch_style=null, $column_index=null, array $ctor_args=null)
	{
		return call_user_func_array([ $this, 'fetchAll' ], func_get_args());
	}
}

/**
 * Exception thrown when a statement execution failed because of an error.
 *
 * @property-read string $statement The invalid statement.
 * @property-read array $args The arguments of the statement.
 */
class StatementInvalid extends ActiveRecordException
{
	private $statement;
	private $args;

	public function __construct($statement, $code=500, \PDOException $previous)
	{
		$message = null;
		$args = null;

		if (is_array($statement))
		{
			list($statement, $args) = $statement;
		}

		$this->statement = $statement;
		$this->args = $args;

		if ($previous)
		{
			$er = array_pad($previous->errorInfo, 3, '');

			$message = sprintf('%s(%s) %s — ', $er[0], $er[1], $er[2]);
		}

		$message .= "`$statement`";

		if ($args)
		{
			$message .= " " . ($args ? json_encode($args) : "[]");
		}

		parent::__construct($message, $code, $previous);
	}

	public function __get($property)
	{
		switch ($property)
		{
			case 'args': return $this->args;
			case 'statement': return $this->statement;
		}

		throw new PropertyNotDefined([ $property, $this ]);
	}
}