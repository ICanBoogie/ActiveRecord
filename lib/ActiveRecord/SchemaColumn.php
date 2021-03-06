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
use ICanBoogie\PropertyNotDefined;

/**
 * Representation of a schema column.
 *
 * @property-read string $formatted_attributes
 * @property-read string $formatted_auto_increment
 * @property-read string $formatted_charset
 * @property-read string $formatted_comment
 * @property-read string $formatted_default
 * @property-read string $formatted_index
 * @property-read string $formatted_null
 * @property-read string $formatted_type
 * @property-read string|array $primary
 */
class SchemaColumn
{
	const TYPE_BLOB = 'blob';
	const TYPE_BOOLEAN = 'boolean';
	const TYPE_INTEGER = 'integer';
	const TYPE_TEXT = 'text';
	const TYPE_VARCHAR = 'varchar';

	use AccessorTrait;

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var string|int
	 */
	public $size;

	/**
	 * @var bool
	 */
	public $unsigned = false;

	/**
	 * @var mixed
	 */
	public $default;

	/**
	 * @var bool
	 */
	public $null = false;

	/**
	 * @var bool
	 */
	public $unique = false;

	/**
	 * @var bool
	 */
	protected $primary = false;

	protected function get_primary()
	{
		return $this->primary;
	}

	/**
	 * @var bool
	 */
	public $auto_increment = false;

	/**
	 * @var bool
	 */
	public $indexed = false;

	/**
	 * @var string
	 */
	public $charset;

	/**
	 * @var string
	 */
	public $comment;

	/**
	 * @param array $options
	 */
	public function __construct(array $options)
	{
		static $option_translate = [

			0 => 'type',
			1 => 'size',
			'auto increment' => 'auto_increment'

		];

		foreach ($options as $option => $value)
		{
			if (isset($option_translate[$option]))
			{
				$option = $option_translate[$option];
			}

			if (!property_exists($this, $option))
			{
				throw new PropertyNotDefined([ $option, $this ]);
			}

			$this->$option = $value;
		}

		switch ($this->type)
		{
			case 'serial':

				$this->type = self::TYPE_INTEGER;
				$this->size = 'big';
				$this->unsigned = true;
				$this->primary = true;
				$this->null = false;
				$this->auto_increment = true;

				break;

			case 'foreign':

				$this->type = self::TYPE_INTEGER;
				$this->size = 'big';
				$this->unsigned = true;
				$this->null = false;
				$this->indexed = !$this->primary;

				break;
		}
	}

	/**
	 * Returns the formatted type, including the size.
	 *
	 * @return string
	 */
	protected function get_formatted_type()
	{
		$rc = '';

		$type = $this->type;
		$size = $this->size;

		if (!$size && $type == self::TYPE_VARCHAR)
		{
			$size = 255;
		}

		switch ($type)
		{
			case self::TYPE_INTEGER:
			case self::TYPE_TEXT:
			case self::TYPE_BLOB:

				$t = [

					self::TYPE_BLOB => 'BLOB',
					self::TYPE_INTEGER => 'INT',
					self::TYPE_TEXT => 'TEXT',

				][ $type ];

				if (is_numeric($size))
				{
					$rc .= "$t( $size )";
				}
				else
				{
					$rc .= strtoupper($size) . $t;
				}

				break;

			default:

				if ($size)
				{
					$rc .= strtoupper($type) . "( $size )";
				}
				else
				{
					$rc .= strtoupper($type);
				}
		}

		return $rc;
	}

	/**
	 * Returns the formatted default.
	 *
	 * @return string
	 */
	protected function get_formatted_default()
	{
		$default = $this->default;

		if (!$default)
		{
			return '';
		}

		switch ($default)
		{
			case 'CURRENT_TIMESTAMP':

				return "DEFAULT $default";

			default:

				return "DEFAULT '$default'";
		}
	}

	/**
	 * Returns the formatted attributes.
	 *
	 * @return string
	 */
	protected function get_formatted_attributes()
	{
		return $this->unsigned ? 'UNSIGNED' : '';
	}

	/**
	 * Returns the formatted null.
	 *
	 * @return string
	 */
	protected function get_formatted_null()
	{
		return $this->null ? 'NULL' : 'NOT NULL';
	}

	/**
	 * Returns the formatted index.
	 *
	 * @return string
	 */
	protected function get_formatted_index()
	{
		return implode(' ', array_filter([

			$this->primary ? 'PRIMARY KEY' : '',
			$this->unique ? 'UNIQUE' : ''

		]));
	}

	/**
	 * Returns the formatted comment.
	 *
	 * @return string
	 */
	protected function get_formatted_comment()
	{
		return $this->comment ? "`$this->comment`" : '';
	}

	/**
	 * Returns the formatted charset.
	 *
	 * @return string
	 */
	protected function get_formatted_charset()
	{
		$charset = $this->charset;

		if (!$charset)
		{
			return '';
		}

		list($charset, $collate) = explode('/', $charset) + [ 1 => null ];

		return "CHARSET $charset" . ($collate ? " COLLATE {$charset}_{$collate}" : '');
	}

	/**
	 * Returns the formatted auto increment.
	 *
	 * @return string
	 */
	protected function get_formatted_auto_increment()
	{
		return $this->auto_increment ? 'AUTO_INCREMENT' : '';
	}

	/**
	 * Whether the column is a serial column.
	 *
	 * @return bool
	 */
	protected function get_is_serial()
	{
		return $this->type == self::TYPE_INTEGER && !$this->null && $this->auto_increment && $this->primary;
	}

	/**
	 * Renders the column into a string.
	 *
	 * @return string
	 */
	public function render()
	{
		return implode(' ', array_filter([

			$this->formatted_type,
			$this->formatted_attributes,
			$this->formatted_charset,
			$this->formatted_null,
			$this->formatted_auto_increment,
			$this->formatted_default,
			$this->formatted_comment

		]));
	}

	public function __toString()
	{
		return (string) $this->render();
	}
}
