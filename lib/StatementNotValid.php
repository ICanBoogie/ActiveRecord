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
 * Exception thrown in attempt to execute a statement that is not valid.
 *
 * @property-read string $statement The invalid statement.
 * @property-read array $args The arguments of the statement.
 * @property-read \PDOException $original The original exception.
 */
class StatementNotValid extends \RuntimeException implements Exception
{
	use AccessorTrait;

	/**
	 * @var string
	 */
	private $statement;

	protected function get_statement()
	{
		return $this->statement;
	}

	/**
	 * @var array
	 */
	private $args;

	protected function get_args()
	{
		return $this->args;
	}

	/**
	 * @var \PDOException
	 */
	private $original;

	protected function get_original()
	{
		return $this->original;
	}

	/**
	 * @param array|string $statement
	 * @param int $code
	 * @param \PDOException|null $original
	 */
	public function __construct($statement, $code = 500, \PDOException $original = null)
	{
		$args = [];

		if (is_array($statement))
		{
			list($statement, $args) = $statement;
		}

		$this->statement = $statement;
		$this->args = $args;
		$this->original = $original;

		parent::__construct($this->format_message($statement, $args, $original), $code);
	}

	/**
	 * Formats exception message.
	 *
	 * @param string $statement
	 * @param array $args
	 * @param \PDOException $original
	 *
	 * @return string
	 */
	protected function format_message($statement, array $args, \PDOException $original = null)
	{
		$message = null;

		if ($original)
		{
			$er = array_pad($original->errorInfo, 3, '');

			$message = sprintf('%s(%s) %s — ', $er[0], $er[1], $er[2]);
		}

		$message .= "`$statement`";

		if ($args)
		{
			$message .= " " . ($args ? json_encode($args) : "[]");
		}

		return $message;
	}
}
