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
 * Exception thrown when the execution of a statement fails.
 *
 * @property-read Statement $statement
 * @property-read array $args
 */
class StatementInvocationFailed extends \LogicException implements Exception
{
	use AccessorTrait;

	/**
	 * @var Statement
	 * @uses get_statement
	 */
	private $statement;

	private function get_statement()
	{
		return $this->statement;
	}

	/**
	 * @var array
	 * @uses get_args
	 */
	private $args;

	private function get_args()
	{
		return $this->args;
	}

	public function __construct(Statement $statement, array $args, string $message = null, int $code = 500, \Throwable $previous = null)
	{
		$this->statement = $statement;
		$this->args = $args;

		parent::__construct($message ?: $this->format_message($statement, $args), $code, $previous);
	}

	/**
	 * Formats a message from a statement and its arguments.
	 *
	 * @param Statement $statement
	 * @param array $args
	 *
	 * @return string
	 */
	private function format_message(Statement $statement, array $args)
	{
		return "Statement execution failed: {$statement->queryString}, with: " . \json_encode($args);
	}
}
