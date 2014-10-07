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

/**
 * Exception thrown in attempt to execute a statement that is not valid.
 *
 * @property-read string $statement The invalid statement.
 * @property-read array $args The arguments of the statement.
 * @property-read \PDOException $original The original exception.
 */
class StatementNotValid extends \RuntimeException implements Exception
{
	use \ICanBoogie\GetterTrait;

	private $statement;

	protected function get_statement()
	{
		return $this->statement;
	}

	private $args;

	protected function get_args()
	{
		return $this->args;
	}

	private $original;

	protected function get_original()
	{
		return $this->original;
	}

	public function __construct($statement, $code=500, \PDOException $original=null)
	{
		$message = null;
		$args = null;

		if (is_array($statement))
		{
			list($statement, $args) = $statement;
		}

		$this->statement = $statement;
		$this->args = $args;
		$this->original = $original;

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

		parent::__construct($message, $code);
	}
}