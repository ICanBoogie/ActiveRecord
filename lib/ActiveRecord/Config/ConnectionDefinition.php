<?php

namespace ICanBoogie\ActiveRecord\Config;

final class ConnectionDefinition
{
    public const DEFAULT_CHARSET_AND_COLLATE = "utf8/general_ci";

    public const DEFAULT_TIMEZONE = '+00:00';

    /**
     * @param array{
     *     id: string,
     *     dsn: string,
     *     username: ?string,
     *     password: ?string,
     *     table_name_prefix: ?string,
     *     charset_and_collate: string,
     *     time_zone: string,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    public function __construct(
        public readonly string $id,
        public readonly string $dsn,
        public readonly ?string $username = null,
        public readonly ?string $password = null,
        public readonly ?string $table_name_prefix = null,
        public readonly string $charset_and_collate = self::DEFAULT_CHARSET_AND_COLLATE,
        public readonly string $time_zone = self::DEFAULT_TIMEZONE,
    ) {
    }
}
