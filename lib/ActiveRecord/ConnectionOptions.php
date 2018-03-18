<?php

namespace ICanBoogie\ActiveRecord;

final class ConnectionOptions
{
	/**
	 * Default charset.
	 */
	const DEFAULT_CHARSET = 'utf8';

	/**
	 * Default collate.
	 */
	const DEFAULT_COLLATE = 'utf8_general_ci';

	/**
	 * Default value for {@link CHARSET_AND_COLLATE}.
	 */
	const DEFAULT_CHARSET_AND_COLLATE = "utf8/general_ci";

	/**
	 * Default value for {@link DEFAULT_TABLE_NAME_PREFIX}.
	 */
	const DEFAULT_TABLE_NAME_PREFIX = '';

	/**
	 * Default value for {@link TIMEZONE}.
	 */
	const DEFAULT_TIMEZONE = '+00:00';

	/**
	 * Connection identifier.
	 */
	const ID = '#id';

	/**
	 * Charset and collate.
	 *
	 * Default: {@link DEFAULT_CHARSET_AND_COLLATE}.
	 */
	const CHARSET_AND_COLLATE = '#charset_and_collate';

	/**
	 * Table name prefix.
	 */
	const TABLE_NAME_PREFIX = '#table_name_prefix';

	/**
	 * Time zone offset used for the connection.
	 *
	 * Default: {@link DEFAULT_TIMEZONE}.
	 */
	const TIMEZONE = '#timezone';

	/**
	 * Normalizes options.
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	static public function normalize(array $options): array
	{
		return $options + [

			self::CHARSET_AND_COLLATE => self::DEFAULT_CHARSET_AND_COLLATE,
			self::ID => null,
			self::TABLE_NAME_PREFIX => self::DEFAULT_TABLE_NAME_PREFIX,
			self::TIMEZONE => self::DEFAULT_TIMEZONE

		];
	}
}
