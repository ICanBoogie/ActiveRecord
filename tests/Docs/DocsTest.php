<?php

namespace Test\ICanBoogie\Docs;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

use function array_map;
use function escapeshellarg;
use function exec;
use function explode;
use function glob;
use function implode;
use function in_array;
use function str_starts_with;
use function trim;

/**
 * Runs the PHP blocks of the documentation, each in its own process, after the setup blocks of
 * {@see DocBlock::SETUP_FILE}.
 */
#[Group("db")]
final class DocsTest extends DbTestCase
{
    private const string ROOT = __DIR__ . '/../..';

    /**
     * @return iterable<string, array{ DocBlock }>
     */
    public static function provide_blocks(): iterable
    {
        $files = [ 'README.md', ...array_map(
            fn(string $path) => substr($path, strlen(self::ROOT) + 1),
            [ ...glob(self::ROOT . '/docs/*.md'), ...glob(self::ROOT . '/docs/*/*.md') ]
        ) ];

        foreach ($files as $file) {
            foreach (DocBlock::from_file(self::ROOT, $file) as $block) {
                if ($block->marker !== 'setup') {
                    yield $block->id => [ $block ];
                }
            }
        }
    }

    public function test_setup(): void
    {
        $this->assert_runs(null);
    }

    #[DataProvider('provide_blocks')]
    public function test_block(DocBlock $block): void
    {
        match ($block->marker) {
            'skip' => $this->markTestSkipped($block->reason ?? ''),
            'excerpt' => $this->assert_excerpt($block),
            'mysql' => str_starts_with(Fixtures::dsn(), 'mysql:')
                ? $this->assert_runs($block)
                : $this->markTestSkipped("MySQL only"),
            default => $this->assert_runs($block),
        };
    }

    private function assert_runs(?DocBlock $block): void
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/run.php');

        if ($block) {
            $command .= ' ' . escapeshellarg($block->file) . ' ' . $block->line;
        }

        exec("cd " . escapeshellarg(self::ROOT) . " && $command 2>&1", $output, $exit_code);

        $this->assertSame(0, $exit_code, implode("\n", $output));
        $this->assertSame("OK", $output[array_key_last($output)], implode("\n", $output));
    }

    /**
     * Asserts that each line of an excerpt appears in the setup code.
     */
    private function assert_excerpt(DocBlock $block): void
    {
        $setup = [];

        foreach (DocBlock::setup_blocks(self::ROOT) as $setup_block) {
            foreach (explode("\n", $setup_block->code) as $line) {
                $setup[] = trim($line);
            }
        }

        foreach (explode("\n", $block->code) as $line) {
            $line = trim($line);

            if (in_array($line, [ '', '<?php', '{', '}', '// …' ], true)) {
                continue;
            }

            $this->assertContains($line, $setup, "$block->id: the excerpt line is not in the setup");
        }
    }
}
