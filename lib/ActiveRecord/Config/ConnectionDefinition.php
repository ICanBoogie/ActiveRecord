<?php

namespace ICanBoogie\ActiveRecord\Config;

use SensitiveParameter;

final class ConnectionDefinition
{
    public const DEFAULT_CHARSET_AND_COLLATE = "utf8/general_ci";

    public const DEFAULT_TIMEZONE = '+00:00';

    /**
     * @param array{
     *     id: non-empty-string,
     *     dsn: non-empty-string,
     *     username: ?non-empty-string,
     *     password: ?non-empty-string,
     *     table_name_prefix: ?non-empty-string,
     *     charset_and_collate: non-empty-string,
     *     time_zone: non-empty-string,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param non-empty-string $id
     * @param non-empty-string $dsn
     * @param non-empty-string|null $username
     * @param non-empty-string|null $password
     * @param non-empty-string|null $table_name_prefix
     * @param non-empty-string $charset_and_collate
     * @param non-empty-string $time_zone
     */
    public function __construct(
        public readonly string $id,
        public readonly string $dsn,
        #[SensitiveParameter]
        public readonly ?string $username = null,
        #[SensitiveParameter]
        public readonly ?string $password = null,
        public readonly ?string $table_name_prefix = null,
        public readonly string $charset_and_collate = self::DEFAULT_CHARSET_AND_COLLATE,
        public readonly string $time_zone = self::DEFAULT_TIMEZONE,
    ) {
    }
}
