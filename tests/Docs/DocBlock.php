<?php

namespace Test\ICanBoogie\Docs;

use LogicException;

use function preg_match;
use function preg_match_all;
use function str_starts_with;
use function trim;

/**
 * A PHP code block of the documentation, with its optional `<!-- doc-test: … -->` marker.
 *
 * Markers:
 *
 * - `setup`: Defines the example records and data, run before every other block.
 * - `excerpt`: Shows parts of the setup, each line must appear in the setup code.
 * - `mysql`: Only runs on MySQL.
 * - `skip: <reason>`: Not run.
 */
final readonly class DocBlock
{
    public const string SETUP_FILE = 'docs/GettingStarted.md';

    /**
     * @return list<self>
     */
    public static function from_file(string $root, string $file): array
    {
        $lines = file("$root/$file", FILE_IGNORE_NEW_LINES)
            ?: throw new LogicException("Unable to read $file");

        $blocks = [];
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            if (trim($lines[$i]) !== '```php') {
                continue;
            }

            $marker = null;
            $reason = null;

            if ($i > 0 && preg_match('/^<!-- doc-test: (\w+)(?::\s*(.+?))? -->$/', trim($lines[$i - 1]), $m)) {
                $marker = $m[1];
                $reason = $m[2] ?? null;
            }

            $start = $i + 1;
            $code = [];

            for ($i = $start; $i < $count && trim($lines[$i]) !== '```'; $i++) {
                $code[] = $lines[$i];
            }

            $blocks[] = new self($file, $start, implode("\n", $code) . "\n", $marker, $reason);
        }

        return $blocks;
    }

    /**
     * @return list<self>
     */
    public static function setup_blocks(string $root): array
    {
        return array_values(array_filter(
            self::from_file($root, self::SETUP_FILE),
            fn(self $block) => $block->marker === 'setup'
        ));
    }

    /**
     * Where the block is, as `<file>:<line>`.
     */
    public string $id;

    /**
     * @param int $line The line of the opening fence.
     */
    public function __construct(
        public string $file,
        public int $line,
        public string $code,
        public ?string $marker,
        public ?string $reason,
    ) {
        $this->id = "$file:$line";

        match ($marker) {
            null, 'setup', 'excerpt', 'mysql' => null,
            'skip' => $reason ?? throw new LogicException("$this->id: a skipped block needs a reason"),
            default => throw new LogicException("$this->id: unknown marker '$marker'"),
        };
    }

    /**
     * The `@var` annotations of the block, as variable name => type.
     *
     * @return array<string, string>
     */
    public function var_annotations(): array
    {
        preg_match_all('#/\*\s*@var\s+\$(\w+)\s+([\\\\\w]+)\s*\*/#', $this->code, $matches, PREG_SET_ORDER);

        $vars = [];

        foreach ($matches as [ , $name, $type ]) {
            $vars[$name] = $this->resolve_class($type);
        }

        return $vars;
    }

    /**
     * Resolves a class name with the `use` statements and the namespace of the block.
     */
    private function resolve_class(string $name): string
    {
        if (str_starts_with($name, '\\')) {
            return substr($name, 1);
        }

        [ $first ] = explode('\\', $name, 2);

        if (preg_match('/^use\s+([\\\\\w]+\\\\' . preg_quote($first, '/') . ');$/m', $this->code, $m)) {
            return $m[1] . substr($name, strlen($first));
        }

        if (preg_match('/^namespace\s+([\\\\\w]+);$/m', $this->code, $m)) {
            return "$m[1]\\$name";
        }

        return $name;
    }
}
