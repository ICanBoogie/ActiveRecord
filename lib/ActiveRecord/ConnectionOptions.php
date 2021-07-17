<?php

namespace ICanBoogie\ActiveRecord;

final class ConnectionOptions
{
    /**
     * Default charset.
     */
    public const DEFAULT_CHARSET = 'utf8';

    /**
     * Default collate.
     */
    public const DEFAULT_COLLATE = 'utf8_general_ci';

    /**
     * Default value for {@link CHARSET_AND_COLLATE}.
     */
    public const DEFAULT_CHARSET_AND_COLLATE = "utf8/general_ci";

    /**
     * Default value for {@link DEFAULT_TABLE_NAME_PREFIX}.
     */
    public const DEFAULT_TABLE_NAME_PREFIX = '';

    /**
     * Default value for {@link TIMEZONE}.
     */
    public const DEFAULT_TIMEZONE = '+00:00';

    /**
     * Connection identifier.
     */
    public const ID = '#id';

    /**
     * Charset and collate.
     *
     * Default: {@link DEFAULT_CHARSET_AND_COLLATE}.
     */
    public const CHARSET_AND_COLLATE = '#charset_and_collate';

    /**
     * Table name prefix.
     */
    public const TABLE_NAME_PREFIX = '#table_name_prefix';

    /**
     * Time zone offset used for the connection.
     *
     * Default: {@link DEFAULT_TIMEZONE}.
     */
    public const TIMEZONE = '#timezone';

    /**
     * Normalizes options.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public static function normalize(array $options): array
    {
        return $options + [
                self::CHARSET_AND_COLLATE => self::DEFAULT_CHARSET_AND_COLLATE,
                self::ID => null,
                self::TABLE_NAME_PREFIX => self::DEFAULT_TABLE_NAME_PREFIX,
                self::TIMEZONE => self::DEFAULT_TIMEZONE
            ];
    }
}
