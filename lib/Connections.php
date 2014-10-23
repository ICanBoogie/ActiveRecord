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
 * Connection collection.
 *
 * @property-read array[string]array $definitions Connection definitions.
 * @property-read Database $established Established connections.
 */
class Connections implements \ArrayAccess, \IteratorAggregate
{
	use \ICanBoogie\GetterTrait;

	/**
	 * Connection definitions.
	 *
	 * @var array[string]array
	 */
	private $definitions;

	protected function get_definitions()
	{
		return $this->definitions;
	}

	/**
	 * Established connections.
	 *
	 * @var array[string]Database
	 */
	private $established = [];

	protected function get_established()
	{
		return $this->established;
	}

	/**
	 * Initialize the {@link $definitions} property.
	 *
	 * @param array $definitions Connection definitions.
	 */
	public function __construct(array $definitions)
	{
		foreach ($definitions as $id => $definition)
		{
			$this[$id] = $definition;
		}
	}

	/**
	 * Checks if a connection definition exists.
	 */
	public function offsetExists($id)
	{
		return isset($this->definitions[$id]);
	}

	/**
	 * Sets the definition of a connection.
	 *
	 * @throws ConnectionAlreadyEstablished in attempt to set the definition of an already
	 * established connection.
	 */
	public function offsetSet($id, $definition)
	{
		if (isset($this->established[$id]))
		{
			throw new ConnectionAlreadyEstablished($id);
		}

		if (is_string($definition))
		{
			$definition = [ 'dsn' => $definition ];
		}

		if (empty($definition['dsn']))
		{
			throw new \InvalidArgumentException("<q>dsn</q> is empty or not defined.");
		}

		$this->definitions[$id] = $definition;
	}

	/**
	 * @throws ConnectionAlreadyEstablished in attempt to unset the definition of an already
	 * established connection.
	 */
	public function offsetUnset($id)
	{
		if (isset($this->established[$id]))
		{
			throw new ConnectionAlreadyEstablished($id);
		}

		unset($this->definitions[$id]);
	}

	/**
	 * Returns a connection to the specified database.
	 *
	 * If the connection has not been established yet, it is created on the fly.
	 *
	 * @param string $id The name of the connection to get.
	 *
	 * @return Database
	 *
	 * @throws ConnectionNotDefined when the connection requested is not defined.
	 * @throws ConnectionNotEstablished when the connection failed.
	 */
	public function offsetGet($id)
	{
		if (isset($this->established[$id]))
		{
			return $this->established[$id];
		}

		if (!$this->offsetExists($id))
		{
			throw new ConnectionNotDefined($id);
		}

		$options = $this->definitions[$id] + [

			'dsn' => null,
			'username' => 'root',
			'password' => null
		];

		$options['options'][Connection::ID] = $id;

		#
		# we catch connection exceptions and rethrow them in order to avoid displaying sensible
		# information such as the username or password.
		#

		try
		{
			return $this->established[$id] = new Connection($options['dsn'], $options['username'], $options['password'], $options['options']);
		}
		catch (\PDOException $e)
		{
			throw new ConnectionNotEstablished($id, "Connection not established: " . $e->getMessage() . ".", 500);
		}
	}

	/**
	 * Returns an iterator for established connections.
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->established);
	}
}
